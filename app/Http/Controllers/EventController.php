<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Attachment;
use App\Models\User;
use App\Models\Location;
use App\Models\Department;
use App\Models\EventHistory;
use Illuminate\Http\Request;
use App\Http\Requests\Event\EventStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventChangedNotification;
use App\Notifications\EventReapprovedNotification;

class EventController extends Controller
{
    private function authenticateUser(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            Log::warning('No Bearer Token provided', [
                'method' => $request->method(),
                'url'    => $request->fullUrl(),
                'ip'     => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if (!$accessToken) {
            Log::warning('Invalid or expired token', ['token' => $token]);
            return null;
        }

        return $accessToken->tokenable_type === User::class
            ? User::find($accessToken->tokenable_id)
            : null;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);

            // Lấy tham số phân trang và sắp xếp từ request
            $perPage = $request->query('per_page');
            $page = $request->query('page', 1);
            $sortField = $request->query('sort_field', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');
            $search = $request->query('search');
            $mode = $request->query('mode', 'all');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $queryUserId = $request->query('user_id');

            // Xác thực tham số
            // Luôn sắp xếp theo start_time
            $sortField = 'start_time';
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
            $mode = in_array($mode, ['all', 'only']) ? $mode : 'all';

            // Khởi tạo truy vấn cơ bản
            $query = Event::with(['locations', 'attachments', 'preparers', 'creator']);

            // Áp dụng tìm kiếm theo title nếu có
            if ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            }

            // Nếu có user_id truyền vào, kiểm tra quyền và lọc theo user đó
            if ($queryUserId) {
                $targetUser = User::find($queryUserId);
                if (!$targetUser) {
                    return $this->sendError('Người dùng không tồn tại.', Response::HTTP_NOT_FOUND);
                }
                // Nếu là user đang đăng nhập
                if ($user && $user->id == $targetUser->id) {
                    // Nếu có quyền chỉnh sửa tất cả sự kiện
                    if ($user->hasPermissionTo('edit_all_events')) {
                        // Không filter theo creator_id, chỉ filter theo các điều kiện khác
                    } elseif ($user->hasPermissionTo('edit_own_events')) {
                        // Chỉ xem sự kiện do chính mình tạo
                        $query->where('creator_id', $user->id);
                    } else {
                        // Không có quyền xem sự kiện
                        return $this->sendError('Bạn không có quyền xem sự kiện.', Response::HTTP_FORBIDDEN);
                    }
                } else {
                    // Nếu không phải user đang đăng nhập, chỉ cho xem sự kiện đã duyệt của user đó
                    $query->where('creator_id', $targetUser->id)
                        ->where('status', 'approved');
                }
            } else {
                // Không truyền user_id, xử lý như cũ
                if ($user) {
                    if ($mode === 'only') {
                        // When in 'only' mode, also load the user's marking information
                        $query->with(['markedByUsers' => function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        }]);

                        // Chỉ lọc các sự kiện mà người dùng là host, thuộc participants, hoặc đã ghim
                        $query->where(function ($q) use ($user) {
                            // User là host của sự kiện
                            $q->where('host_id', $user->id);

                            // HOẶC user là người tham gia (dạng JSON)
                            $q->orWhereJsonContains('participants', ['type' => 'user', 'id' => $user->id]);

                            // HOẶC sự kiện đã được user ghim/đánh dấu quan tâm
                            $q->orWhereHas('markedByUsers', function ($markedQuery) use ($user) {
                                $markedQuery->where('user_id', $user->id)
                                    ->where('is_marked', true);
                            });

                            // HOẶC phòng ban của user là người tham gia
                            $departmentIds = $user->departments()->pluck('departments.id')->toArray();
                            if ($user->primary_department_id) {
                                $departmentIds[] = $user->primary_department_id;
                            }

                            // Nếu có phòng ban
                            if (!empty($departmentIds)) {
                                foreach ($departmentIds as $deptId) {
                                    $q->orWhereJsonContains('participants', ['type' => 'department', 'id' => $deptId]);
                                }
                            }
                        });
                    } else {
                        // Xử lý mode='all' như hiện tại
                        $query->where(function ($q) use ($user) {
                            $q->where('creator_id', $user->id)
                                ->orWhere('status', 'approved');
                        });
                    }
                } else {
                    $query->where('status', "approved");
                }
            }

            // Xử lý khoảng thời gian: từ start_date đến cuối ngày của end_date
            if ($startDate && $endDate) {
                // Chuyển end_date thành cuối ngày (23:59:59)
                $endDateWithTime = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

                $query->where(function ($q) use ($startDate, $endDateWithTime) {
                    // Sự kiện có start_time trong khoảng thời gian
                    $q->whereBetween('start_time', [$startDate, $endDateWithTime])
                        // HOẶC có end_time trong khoảng thời gian
                        ->orWhereBetween('end_time', [$startDate, $endDateWithTime])
                        // HOẶC sự kiện bắt đầu trước khoảng thời gian và kết thúc sau khoảng thời gian
                        ->orWhere(function ($query) use ($startDate, $endDateWithTime) {
                            $query->where('start_time', '<=', $startDate)
                                ->where('end_time', '>=', $endDateWithTime);
                        });
                });
            }

            // Nếu không truyền per_page, lấy tất cả sự kiện của 1 tuần dựa theo start_time
            if (is_null($perPage)) {
                // Nếu không truyền start_date/end_date thì lấy tuần hiện tại
                if (!$startDate || !$endDate) {
                    $startOfWeek = now()->startOfWeek()->toDateString();
                    $endOfWeek = now()->endOfWeek()->endOfDay()->toDateTimeString();
                    $query->where(function ($q) use ($startOfWeek, $endOfWeek) {
                        $q->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                            ->orWhereBetween('end_time', [$startOfWeek, $endOfWeek])
                            ->orWhere(function ($query) use ($startOfWeek, $endOfWeek) {
                                $query->where('start_time', '<=', $startOfWeek)
                                    ->where('end_time', '>=', $endOfWeek);
                            });
                    });
                }
                // Mặc định lấy tất cả, nhưng vẫn trả về thông tin phân trang
                $events = $query->orderBy('start_time', $sortOrder)->get();

                // Ensure host is returned as ID
                $events->transform(function ($event) {
                    // Make sure 'host' is an ID, not an object
                    if (is_object($event->host)) {
                        $event->host = $event->host->id;
                    }
                    return $event;
                });

                $total = $events->count();
                $page = max((int) $page, 1);
                $perPage = $total > 0 ? $total : 1;
                $lastPage = $total > 0 ? 1 : 0;

                // Add user-specific information to each event if user is authenticated
                if ($user && $mode === 'only') {
                    foreach ($events as $event) {
                        $event->is_marked = $event->markedByUsers->contains('id', $user->id) &&
                            $event->markedByUsers->where('id', $user->id)->first()->pivot->is_marked;
                        $event->is_viewed = $event->markedByUsers->contains('id', $user->id) &&
                            $event->markedByUsers->where('id', $user->id)->first()->pivot->is_viewed;
                        // Remove the relationship data from the response
                        unset($event->markedByUsers);
                    }
                }

                return $this->sendSuccess([
                    'events' => $events,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'sort_field' => $sortField,
                    'sort_order' => $sortOrder,
                    'message' => 'Danh sách sự kiện trong khoảng thời gian đã được lấy thành công.',
                ], Response::HTTP_OK);
            } else {
                // Nếu có per_page, phân trang như bình thường
                $perPage = min(max((int) $perPage, 1), 100);
                $page = max((int) $page, 1);

                // Nếu user đăng nhập và không có quyền xem tất cả, chỉ trả về sự kiện của các user cùng vai trò
                if ($user && (!$user->hasPermissionTo('edit_all_events') && !$user->hasPermissionTo('delete_all_events'))) {
                    // Lấy danh sách user_id của những người có cùng vai trò
                    $roleIds = $user->roles->pluck('id')->toArray();
                    $userIdsWithSameRoles = User::whereHas('roles', function ($q) use ($roleIds) {
                        $q->whereIn('id', $roleIds);
                    })->pluck('id')->toArray();

                    $query->whereIn('creator_id', $userIdsWithSameRoles);
                }

                $events = $query->orderBy('start_time', $sortOrder)
                    ->paginate($perPage, ['*'], 'page', $page);

                $eventItems = $events->items();

                // Ensure host is returned as ID
                foreach ($eventItems as $event) {
                    if (is_object($event->host)) {
                        $event->host = $event->host->id;
                    }
                }

                // Add user-specific information to each event if user is authenticated
                if ($user && $mode === 'only') {
                    foreach ($eventItems as $event) {
                        $event->is_marked = $event->markedByUsers->contains('id', $user->id) &&
                            $event->markedByUsers->where('id', $user->id)->first()->pivot->is_marked;
                        $event->is_viewed = $event->markedByUsers->contains('id', $user->id) &&
                            $event->markedByUsers->where('id', $user->id)->first()->pivot->is_viewed;
                        // Remove the relationship data from the response
                        unset($event->markedByUsers);
                    }
                }

                return $this->sendSuccess([
                    'events' => $eventItems,
                    'page' => $events->currentPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'last_page' => $events->lastPage(),
                    'sort_field' => $sortField,
                    'sort_order' => $sortOrder,
                    'message' => 'Danh sách sự kiện đã được lấy thành công.',
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sự kiện:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách sự kiện.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(EventStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Lấy dữ liệu đã được xác thực
            $validated = $request->validated();

            // Log request data for debugging
            Log::info('Tạo sự kiện mới - dữ liệu nhận được:', [
                'user_id' => $user->id,
                'validated_data' => $validated,
            ]);

            // Xác thực thời gian
            $startTime = Carbon::parse($validated['start_time']);
            $endTime = Carbon::parse($validated['end_time']);

            if ($endTime->lessThan($startTime)) {
                DB::rollBack();
                return $this->sendError('Thời gian kết thúc phải sau thời gian bắt đầu.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Xử lý location_ids
            $locationIds = $validated['location_ids'] ?? [];
            $hasZeroLocation = in_array(0, $locationIds);
            $locationIdsToValidate = array_filter($locationIds, fn($id) => $id !== 0);
            $validLocationIds = [];

            // Xử lý custom_locations nếu có ID = 0
            if ($hasZeroLocation && !empty($validated['custom_locations'])) {
                if (!$user->hasPermissionTo('create_locations')) {
                    DB::rollBack();
                    return $this->sendError('Bạn không có quyền tạo địa điểm mới.', Response::HTTP_FORBIDDEN);
                }

                // Tách chuỗi custom_locations thành mảng
                $locationNames = array_map('trim', explode(',', $validated['custom_locations']));
                foreach ($locationNames as $name) {
                    if (!empty($name)) {
                        try {
                            // Kiểm tra xem địa điểm đã tồn tại chưa
                            $existingLocation = Location::where('name', $name)->first();
                            if ($existingLocation) {
                                $locationIdsToValidate[] = $existingLocation->id;
                            } else {
                                $location = Location::create([
                                    'name' => $name,
                                ]);
                                $locationIdsToValidate[] = $location->id;

                                Log::info('Đã tạo địa điểm mới:', [
                                    'location_id' => $location->id,
                                    'name' => $name
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Lỗi khi tạo địa điểm mới:', [
                                'name' => $name,
                                'error' => $e->getMessage()
                            ]);
                            DB::rollBack();
                            return $this->sendError('Lỗi khi tạo địa điểm mới: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }
                }
            }

            // Kiểm tra location_ids hợp lệ
            if (!empty($locationIdsToValidate)) {
                $validLocationIds = Location::whereIn('id', $locationIdsToValidate)
                    ->pluck('id')
                    ->toArray();

                $invalidIds = array_diff($locationIdsToValidate, $validLocationIds);
                if (!empty($invalidIds)) {
                    DB::rollBack();
                    return $this->sendError('Một hoặc nhiều địa điểm không tồn tại: ' . implode(', ', $invalidIds), Response::HTTP_BAD_REQUEST);
                }
            }

            // Xử lý preparer_ids
            $preparerIds = $validated['preparer_ids'] ?? [];
            $hasZeroPreparer = in_array(0, $preparerIds);
            $preparerIdsToValidate = array_filter($preparerIds, fn($id) => $id !== 0);
            $validPreparerIds = [];

            // Xử lý custom_preparers nếu có ID = 0
            if ($hasZeroPreparer && !empty($validated['custom_preparers'])) {
                if (!$user->hasPermissionTo('create_departments')) {
                    DB::rollBack();
                    return $this->sendError('Bạn không có quyền tạo phòng ban mới.', Response::HTTP_FORBIDDEN);
                }

                // Tách chuỗi custom_preparers thành mảng
                $preparerNames = array_map('trim', explode(',', $validated['custom_preparers']));
                foreach ($preparerNames as $name) {
                    if (!empty($name)) {
                        try {
                            // Kiểm tra xem phòng ban đã tồn tại chưa
                            $existingDepartment = Department::where('name', $name)->first();
                            if ($existingDepartment) {
                                $preparerIdsToValidate[] = $existingDepartment->id;
                            } else {
                                $department = Department::create([
                                    'name' => $name,
                                    'description' => null,
                                ]);
                                $preparerIdsToValidate[] = $department->id;

                                Log::info('Đã tạo phòng ban mới:', [
                                    'department_id' => $department->id,
                                    'name' => $name
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Lỗi khi tạo phòng ban mới:', [
                                'name' => $name,
                                'error' => $e->getMessage()
                            ]);
                            DB::rollBack();
                            return $this->sendError('Lỗi khi tạo phòng ban mới: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }
                }
            }

            // Kiểm tra preparer_ids hợp lệ
            if (!empty($preparerIdsToValidate)) {
                $validPreparerIds = Department::whereIn('id', $preparerIdsToValidate)
                    ->pluck('id')
                    ->toArray();

                $invalidIds = array_diff($preparerIdsToValidate, $validPreparerIds);
                if (!empty($invalidIds)) {
                    DB::rollBack();
                    return $this->sendError('Một hoặc nhiều phòng ban không tồn tại: ' . implode(', ', $invalidIds), Response::HTTP_BAD_REQUEST);
                }
            } else {
                DB::rollBack();
                return $this->sendError('Phải có ít nhất một đơn vị chuẩn bị hợp lệ.', Response::HTTP_BAD_REQUEST);
            }

            // Xử lý participants
            $participants = $validated['participants'] ?? null;
            if ($participants) {
                // Kiểm tra cấu trúc participants có hợp lệ không
                if (!is_array($participants)) {
                    DB::rollBack();
                    return $this->sendError('Dữ liệu người tham gia không hợp lệ. Định dạng yêu cầu là mảng.', Response::HTTP_BAD_REQUEST);
                }

                foreach ($participants as $participant) {
                    if (!isset($participant['type']) || !isset($participant['id'])) {
                        DB::rollBack();
                        return $this->sendError('Dữ liệu người tham gia không hợp lệ. Thiếu trường type hoặc id.', Response::HTTP_BAD_REQUEST);
                    }

                    // Kiểm tra type hợp lệ
                    if (!in_array($participant['type'], ['user', 'department'])) {
                        DB::rollBack();
                        return $this->sendError('Loại người tham gia không hợp lệ. Chỉ chấp nhận "user" hoặc "department".', Response::HTTP_BAD_REQUEST);
                    }

                    // Kiểm tra ID tồn tại
                    if ($participant['type'] === 'user' && !User::find($participant['id'])) {
                        DB::rollBack();
                        return $this->sendError('Không tìm thấy người dùng với ID: ' . $participant['id'], Response::HTTP_BAD_REQUEST);
                    } elseif ($participant['type'] === 'department' && !Department::find($participant['id'])) {
                        DB::rollBack();
                        return $this->sendError('Không tìm thấy phòng ban với ID: ' . $participant['id'], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Tạo sự kiện mới
            try {
                $event = Event::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'host_id' => $validated['host_id'],
                    'participants' => $validated['participants'] ?? null,
                    'status' => $validated['status'] ?? 'pending',
                    'reminder_type' => $validated['reminder_type'] ?? 'none',
                    'reminder_time' => $validated['reminder_time'] ?? null,
                    'creator_id' => $user->id,
                ]);

                Log::info('Đã tạo sự kiện mới:', [
                    'event_id' => $event->id,
                    'title' => $event->title,
                    'creator_id' => $user->id
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi tạo sự kiện:', [
                    'error' => $e->getMessage(),
                    'data' => $validated
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi tạo sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Đồng bộ địa điểm
            try {
                $event->locations()->sync($validLocationIds);

                Log::info('Đã đồng bộ địa điểm:', [
                    'event_id' => $event->id,
                    'location_ids' => $validLocationIds
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi đồng bộ địa điểm:', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                    'location_ids' => $validLocationIds
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi đồng bộ địa điểm: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Đồng bộ preparers
            try {
                $event->preparers()->sync($validPreparerIds);

                Log::info('Đã đồng bộ phòng ban:', [
                    'event_id' => $event->id,
                    'preparer_ids' => $validPreparerIds
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi đồng bộ phòng ban:', [
                    'error' => $e->getMessage(),
                    'event_id' => $event->id,
                    'preparer_ids' => $validPreparerIds
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi đồng bộ phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Liên kết các tệp đính kèm (nếu có)
            if (!empty($validated['attachments'])) {
                try {
                    $validAttachmentIds = Attachment::whereIn('id', $validated['attachments'])
                        ->where(function ($query) use ($user) {
                            $query->where('uploader_id', $user->id)
                                ->orWhereHas('uploader', function ($q) use ($user) {
                                    // Cho phép quản lý xem tệp của người khác
                                    $q->whereHas('roles', function ($r) {
                                        $r->where('name', 'admin');
                                    });
                                });
                        })
                        ->pluck('id')
                        ->toArray();

                    $invalidAttachmentCount = count($validated['attachments']) - count($validAttachmentIds);
                    if ($invalidAttachmentCount > 0) {
                        DB::rollBack();
                        return $this->sendError(
                            'Có ' . $invalidAttachmentCount . ' tệp đính kèm không hợp lệ hoặc bạn không có quyền sử dụng.',
                            Response::HTTP_FORBIDDEN
                        );
                    }

                    $event->attachments()->sync($validAttachmentIds);

                    Log::info('Đã đồng bộ tệp đính kèm:', [
                        'event_id' => $event->id,
                        'attachment_ids' => $validAttachmentIds
                    ]);
                } catch (\Exception $e) {
                    Log::error('Lỗi khi đồng bộ tệp đính kèm:', [
                        'error' => $e->getMessage(),
                        'event_id' => $event->id,
                        'attachment_ids' => $validated['attachments']
                    ]);
                    DB::rollBack();
                    return $this->sendError('Lỗi khi đồng bộ tệp đính kèm: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Commit transaction
            DB::commit();

            // Tải các mối quan hệ để trả về
            $event->load(['locations', 'attachments', 'preparers', 'creator']);

            // Ensure host is returned as ID
            if (is_object($event->host)) {
                $event->host = $event->host->id;
            }

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'event' => $event,
                'message' => 'Sự kiện đã được tạo thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi validation khi tạo sự kiện:', [
                'errors' => $e->validator->errors()->toArray(),
                'user_id' => $user->id ?? null,
            ]);

            return $this->sendError('Dữ liệu không hợp lệ: ' . json_encode($e->validator->errors()->toArray()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi tạo sự kiện:', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi tạo sự kiện. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $event = Event::with(['locations', 'attachments', 'preparers', 'histories.user'])->find($id);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendSuccess([
                'event' => $event,
                'message' => 'Lấy thông tin sự kiện thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin sự kiện:', [
                'event_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy thông tin sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EventStoreRequest $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền edit_events
            if (!$user->hasPermissionTo('edit_all_events') || !$user->hasPermissionTo('edit_own_events')) {
                return $this->sendError('Bạn không có quyền chỉnh sửa sự kiện.', Response::HTTP_FORBIDDEN);
            }

            // Tìm sự kiện
            $event = Event::with(['locations', 'preparers', 'attachments'])->find($id);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Kiểm tra quyền sở hữu
            if ($event->creator_id !== $user->id && !$user->hasPermissionTo('edit_own_events')) {
                return $this->sendError('Bạn chỉ có thể chỉnh sửa sự kiện do chính bạn tạo.', Response::HTTP_FORBIDDEN);
            }

            // Xác thực dữ liệu đầu vào
            $validated = $request->validated();

            // Xử lý location_ids
            $locationIds = $validated['location_ids'] ?? [];
            $hasZeroLocation = in_array(0, $locationIds);
            $locationIdsToValidate = array_filter($locationIds, fn($id) => $id !== 0);

            // Xử lý customer_location nếu có ID = 0
            if ($hasZeroLocation && !empty($validated['customer_location'])) {
                if (!$user->hasPermissionTo('create_locations')) {
                    return $this->sendError('Bạn không có quyền tạo địa điểm mới.', Response::HTTP_FORBIDDEN);
                }
                $locationNames = array_map('trim', explode(',', $validated['customer_location']));
                foreach ($locationNames as $name) {
                    if (!empty($name)) {
                        $location = Location::create([
                            'name' => $name,
                        ]);
                        $locationIdsToValidate[] = $location->id;
                    }
                }
            }

            // Kiểm tra location_ids hợp lệ
            if (!empty($locationIdsToValidate)) {
                $validLocationIds = Location::whereIn('id', $locationIdsToValidate)
                    ->pluck('id')
                    ->toArray();
                if (count($validLocationIds) !== count($locationIdsToValidate)) {
                    return $this->sendError('Một hoặc nhiều địa điểm không hợp lệ.', Response::HTTP_BAD_REQUEST);
                }
            } else {
                $validLocationIds = [];
            }

            // Xử lý preparer_ids
            $preparerIds = $validated['preparer_ids'] ?? [];
            $hasZeroPreparer = in_array(0, $preparerIds);
            $preparerIdsToValidate = array_filter($preparerIds, fn($id) => $id !== 0);

            // Xử lý customer_preparer nếu có ID = 0
            if ($hasZeroPreparer && !empty($validated['customer_preparer'])) {
                if (!$user->hasPermissionTo('create_departments')) {
                    return $this->sendError('Bạn không có quyền tạo phòng ban mới.', Response::HTTP_FORBIDDEN);
                }
                $preparerNames = array_map('trim', explode(',', $validated['customer_preparer']));
                foreach ($preparerNames as $name) {
                    if (!empty($name)) {
                        $department = Department::create([
                            'name' => $name,
                            'description' => null,
                        ]);
                        $preparerIdsToValidate[] = $department->id;
                    }
                }
            }

            // Kiểm tra preparer_ids hợp lệ
            if (!empty($preparerIdsToValidate)) {
                $validPreparerIds = Department::whereIn('id', $preparerIdsToValidate)
                    ->pluck('id')
                    ->toArray();
                if (count($validPreparerIds) !== count($preparerIdsToValidate)) {
                    return $this->sendError('Một hoặc nhiều phòng ban không hợp lệ.', Response::HTTP_BAD_REQUEST);
                }
            } else {
                $validPreparerIds = [];
            }

            // Xử lý attachments
            $attachmentIds = $validated['attachments'] ?? [];
            if (!empty($attachmentIds)) {
                if (!$user->hasPermissionTo('create_attachments')) {
                    return $this->sendError('Bạn không có quyền thêm tệp đính kèm.', Response::HTTP_FORBIDDEN);
                }
                $validAttachmentIds = Attachment::whereIn('id', $attachmentIds)
                    ->where('uploader_id', $user->id)
                    ->pluck('id')
                    ->toArray();
                if (count($validAttachmentIds) !== count($attachmentIds)) {
                    return $this->sendError('Một hoặc nhiều tệp đính kèm không hợp lệ hoặc bạn không có quyền.', Response::HTTP_BAD_REQUEST);
                }
            } else {
                $validAttachmentIds = [];
            }

            // Cập nhật sự kiện trong transaction
            $event = DB::transaction(function () use ($event, $validated, $validLocationIds, $validPreparerIds, $validAttachmentIds, $user) {
                $changes = [];
                $fields = [
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'host_id' => $validated['host_id'],
                    'participants' => $validated['participants'] ?? null,
                    'status' => $validated['status'] ?? 'pending',
                    'reminder_type' => $validated['reminder_type'] ?? 'none',
                    'reminder_time' => $validated['reminder_time'] ?? null,
                ];

                $dateFields = ['start_time', 'end_time', 'reminder_time'];

                foreach ($fields as $field => $newValue) {
                    $oldValue = $event->$field;

                    if (in_array($field, $dateFields)) {
                        // Normalize dates to UTC timestamp for comparison
                        $oldDate = $oldValue ? Carbon::parse($oldValue)->getTimestamp() : null;
                        $newDate = $newValue ? Carbon::parse($newValue)->getTimestamp() : null;

                        if ($oldDate !== $newDate) {
                            $changes[] = [
                                'event_id' => $event->id,
                                'user_id' => $user->id,
                                'field_name' => $field,
                                'old_value' => $oldValue ? Carbon::parse($oldValue)->toDateTimeString() : null,
                                'new_value' => $newValue ? Carbon::parse($newValue)->toDateTimeString() : null,
                            ];
                        }
                    } else {
                        // Non-date fields: direct comparison
                        if ($oldValue != $newValue) {
                            $changes[] = [
                                'event_id' => $event->id,
                                'user_id' => $user->id,
                                'field_name' => $field,
                                'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                                'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
                            ];
                        }
                    }
                }

                // Log changes for relationships
                $oldLocationIds = $event->locations()->pluck('locations.id')->toArray();
                if (array_diff($oldLocationIds, $validLocationIds) || array_diff($validLocationIds, $oldLocationIds)) {
                    $changes[] = [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'field_name' => 'locations',
                        'old_value' => json_encode($oldLocationIds),
                        'new_value' => json_encode($validLocationIds),
                    ];
                }

                $oldPreparerIds = $event->preparers()->pluck('departments.id')->toArray();
                if (array_diff($oldPreparerIds, $validPreparerIds) || array_diff($validPreparerIds, $oldPreparerIds)) {
                    $changes[] = [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'field_name' => 'preparers',
                        'old_value' => json_encode($oldPreparerIds),
                        'new_value' => json_encode($validPreparerIds),
                    ];
                }

                $oldAttachmentIds = $event->attachments()->pluck('attachments.id')->toArray();
                if (array_diff($oldAttachmentIds, $validAttachmentIds) || array_diff($validAttachmentIds, $oldAttachmentIds)) {
                    $changes[] = [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'field_name' => 'attachments',
                        'old_value' => json_encode($oldAttachmentIds),
                        'new_value' => json_encode($validAttachmentIds),
                    ];
                }

                if (!empty($changes)) {
                    EventHistory::insert($changes);
                }

                $event->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'host_id' => $validated['host_id'],
                    'participants' => $validated['participants'] ?? null,
                    'status' => $validated['status'] ?? 'pending',
                    'reminder_type' => $validated['reminder_type'] ?? 'none',
                    'reminder_time' => $validated['reminder_time'] ?? null,
                ]);

                $event->locations()->sync($validLocationIds);
                $event->preparers()->sync($validPreparerIds);
                $event->attachments()->sync($validAttachmentIds);

                return $event->refresh();
            });


            // Tải các quan hệ để trả về
            $event->load(['locations', 'preparers', 'attachments', 'creator']);

            // Gửi thông báo về việc cập nhật sự kiện
            if (!empty($changes) && $event->status === 'approved') {
                // Nếu sự kiện đã được phê duyệt, gửi thông báo thay đổi
                // cho người tạo và người tham gia
                $creator = $event->creator;
                if ($creator && $creator->id !== $user->id) {
                    $creator->notify(new EventChangedNotification($event, $changes, $user));
                }

                // Gửi thông báo cho người tham gia
                if ($event->participants && is_array($event->participants)) {
                    $userParticipants = array_filter($event->participants, function ($p) {
                        return isset($p['type']) && $p['type'] === 'user' && isset($p['id']);
                    });

                    foreach ($userParticipants as $participant) {
                        $participantUser = User::find($participant['id']);
                        if ($participantUser && $participantUser->id !== $user->id && $participantUser->id !== $event->creator_id) {
                            $participantUser->notify(new EventChangedNotification($event, $changes, $user));
                        }
                    }
                }
            }

            // Ghi log hành động cập nhật
            Log::info('Sự kiện đã được cập nhật:', [
                'event_id' => $event->id,
                'title' => $event->title,
                'user_id' => $user->id,
                'location_ids' => $validLocationIds,
                'preparer_ids' => $validPreparerIds,
                'attachment_ids' => $validAttachmentIds,
                'updated_at' => now()->toDateTimeString(),
            ]);

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'data' => $event,
                'location_ids' => $validLocationIds,
                'preparer_ids' => $validPreparerIds,
                'attachment_ids' => $validAttachmentIds,
            ], 'Cập nhật sự kiện thành công.');
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::error('Lỗi khi cập nhật sự kiện:', [
                'event_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi cập nhật sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền delete_events
            if (!$user->hasPermissionTo('delete_all_events') && !$user->hasPermissionTo('delete_own_events')) {
                return $this->sendError('Bạn không có quyền xóa sự kiện.', Response::HTTP_FORBIDDEN);
            }

            // Tìm sự kiện
            $event = Event::with(['locations', 'preparers', 'attachments'])->find($id);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Kiểm tra quyền sở hữu
            if ($event->creator_id !== $user->id && !$user->hasPermissionTo('delete_all_events')) {
                return $this->sendError('Bạn chỉ có thể xóa sự kiện do chính bạn tạo.', Response::HTTP_FORBIDDEN);
            }

            // Kiểm tra quyền xóa attachments
            if ($event->attachments()->exists() && !$user->hasPermissionTo('delete_attachments')) {
                return $this->sendError('Bạn không có quyền xóa các tệp đính kèm liên quan.', Response::HTTP_FORBIDDEN);
            }

            // Xóa sự kiện và các tệp đính kèm trong transaction
            $deletedAttachmentIds = [];
            DB::transaction(function () use ($event, &$deletedAttachmentIds) {
                // Lấy danh sách ID các tệp đính kèm
                $attachmentIds = $event->attachments()->pluck('attachments.id')->toArray();

                Log::info("Attchments", [
                    "attachments" => $event->attachments
                ]);
                // Xóa tệp vật lý (nếu có)
                foreach ($event->attachments as $attachment) {
                    // Xóa tệp vật lý từ storage
                    $filePath = str_replace('/storage/', '', $attachment->file_url);
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    } else {
                        Log::warning("Tệp không tồn tại trong storage: {$filePath}");
                    }
                    $deletedAttachmentIds[] = $attachment->id;
                }

                Attachment::whereIn('id', $deletedAttachmentIds)->delete();

                // Xóa các quan hệ trong bảng trung gian
                $event->locations()->detach();
                $event->preparers()->detach();
                $event->attachments()->detach(); // Đảm bảo quan hệ được xóa sạch

                // Xóa sự kiện (hỗ trợ soft delete nếu có)
                $event->delete();
            });

            // Ghi log hành động xóa
            Log::info('Sự kiện và các tệp đính kèm đã được xóa:', [
                'event_id' => $event->id,
                'title' => $event->title,
                'user_id' => $user->id,
                'deleted_attachment_ids' => $deletedAttachmentIds,
                'deleted_at' => now()->toDateTimeString(),
            ]);

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'id' => $event->id,
                'title' => $event->title,
            ], 'Xóa sự kiện thành công.');
        } catch (\Exception $e) {
            // Ghi log lỗi
            Log::error('Lỗi khi xóa sự kiện:', [
                'event_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi xóa sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/events/{event}/approve
     *
     * Phê duyệt hoặc từ chối sự kiện
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('approve_events')) {
                return $this->sendError('Bạn không có quyền phê duyệt sự kiện.', Response::HTTP_FORBIDDEN);
            }

            $eventData = Event::with(['locations', 'preparers', 'creator'])->find($id);
            if (!$eventData) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'status' => 'required|in:pending,approved,declined',
            ]);

            $wasApproved = false;
            $wasChangedAfterApproved = false;
            $changesCollection = [];
            $changes = [];

            $eventData = DB::transaction(function () use ($eventData, $validated, $user, &$wasApproved, &$wasChangedAfterApproved, &$changesCollection, &$changes) {
                $oldStatus = $eventData->status;

                // Lấy lịch sử thay đổi gần đây của sự kiện
                $changesCollection = EventHistory::where('event_id', $eventData->id)
                    ->where('field_name', '!=', 'status')
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Chuyển đổi collection thành mảng để sử dụng sau
                $changes = [];
                foreach ($changesCollection as $history) {
                    $changes[$history->field_name] = [
                        'old_value' => $history->old_value,
                        'new_value' => $history->new_value
                    ];
                }

                // Kiểm tra xem sự kiện đã được phê duyệt trước đó chưa
                $latestStatusChange = EventHistory::where('event_id', $eventData->id)
                    ->where('field_name', 'status')
                    ->where('new_value', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Nếu có thay đổi nội dung sau khi đã phê duyệt trước đó
                if ($latestStatusChange && $changesCollection->count() > 0) {
                    $latestContentChange = $changesCollection->first();
                    if ($latestContentChange && $latestContentChange->created_at > $latestStatusChange->created_at) {
                        $wasChangedAfterApproved = true;
                    }
                }

                if ($oldStatus !== $validated['status']) {
                    EventHistory::create([
                        'event_id' => $eventData->id,
                        'user_id' => $user->id,
                        'field_name' => 'status',
                        'old_value' => $oldStatus,
                        'new_value' => $validated['status'],
                    ]);

                    // Đánh dấu nếu sự kiện được phê duyệt
                    if ($validated['status'] === 'approved') {
                        $wasApproved = true;
                    }
                }

                $eventData->update([
                    'status' => $validated['status'],
                ]);

                return $eventData->refresh();
            });

            // Gửi thông báo qua email
            if ($wasApproved && $eventData->creator) {
                if ($wasChangedAfterApproved) {
                    // Sự kiện đã được thay đổi và phê duyệt lại
                    $eventData->creator->notify(new \App\Notifications\EventReapprovedNotification($eventData, $changes));
                    Log::info('Đã gửi thông báo phê duyệt lại cho sự kiện:', [
                        'event_id' => $eventData->id,
                        'creator_id' => $eventData->creator->id,
                        'changes' => $changes
                    ]);
                } else {
                    // Sự kiện mới được phê duyệt lần đầu
                    $eventData->creator->notify(new \App\Notifications\EventApprovedNotification($eventData));
                    Log::info('Đã gửi thông báo phê duyệt cho sự kiện:', [
                        'event_id' => $eventData->id,
                        'creator_id' => $eventData->creator->id,
                    ]);
                }

                // Nếu có người tham gia là user, gửi thông báo cho họ
                if ($eventData->participants && is_array($eventData->participants)) {
                    $userParticipants = array_filter($eventData->participants, function ($p) {
                        return isset($p['type']) && $p['type'] === 'user' && isset($p['id']);
                    });

                    foreach ($userParticipants as $participant) {
                        $participantUser = User::find($participant['id']);
                        if ($participantUser && $participantUser->id !== $eventData->creator_id) {
                            if ($wasChangedAfterApproved) {
                                $participantUser->notify(new \App\Notifications\EventReapprovedNotification($eventData, $changes));
                            } else {
                                $participantUser->notify(new \App\Notifications\EventApprovedNotification($eventData));
                            }
                        }
                    }
                }
            }

            Log::info('Sự kiện đã được phê duyệt/từ chối:', [
                'event_id' => $eventData->id,
                'title' => $eventData->title,
                'status' => $eventData->status,
                'user_id' => $user->id,
            ]);

            return $this->sendSuccess([
                'event' => $eventData->load(['locations', 'preparers']),
                'message' => 'Phê duyệt sự kiện thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi phê duyệt sự kiện:', [
                'event_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi phê duyệt sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ghim/Đánh dấu sự kiện
     *
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function markEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $event = Event::find($eventId);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Cập nhật hoặc tạo mới bản ghi trong bảng user_events
            $user->events()->syncWithoutDetaching([
                $eventId => ['is_marked' => true, 'updated_at' => now()]
            ]);

            return $this->sendSuccess([
                'message' => 'Đã ghim sự kiện thành công.',
                'event_id' => $eventId
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi ghim sự kiện:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi ghim sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bỏ ghim/Hủy đánh dấu sự kiện
     *
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function unmarkEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $event = Event::find($eventId);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Cập nhật bản ghi trong bảng user_events
            $user->events()->syncWithoutDetaching([
                $eventId => ['is_marked' => false, 'updated_at' => now()]
            ]);

            return $this->sendSuccess([
                'message' => 'Đã bỏ ghim sự kiện thành công.',
                'event_id' => $eventId
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi bỏ ghim sự kiện:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi bỏ ghim sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Đánh dấu đã xem sự kiện
     *
     * @param Request $request
     * @param int $eventId
     * @return JsonResponse
     */
    public function viewEvent(Request $request, int $eventId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $event = Event::find($eventId);
            if (!$event) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            // Kiểm tra xem bản ghi đã tồn tại chưa
            $existingRecord = DB::table('user_events')
                ->where('user_id', $user->id)
                ->where('event_id', $eventId)
                ->first();

            if ($existingRecord) {
                // Nếu đã tồn tại, chỉ cập nhật trường is_viewed
                $user->events()->syncWithoutDetaching([
                    $eventId => [
                        'is_viewed' => true,
                        'updated_at' => now()
                    ]
                ]);
            } else {
                // Nếu chưa tồn tại, tạo mới bản ghi
                $user->events()->syncWithoutDetaching([
                    $eventId => [
                        'is_viewed' => true,
                        'is_marked' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            }

            return $this->sendSuccess([
                'message' => 'Đã đánh dấu đã xem sự kiện thành công.',
                'event_id' => $eventId
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi đánh dấu đã xem sự kiện:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi đánh dấu đã xem sự kiện: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách sự kiện đã ghim của người dùng
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarkedEvents(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Lấy tham số phân trang và sắp xếp từ request
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $sortField = $request->query('sort_field', 'start_time');
            $sortOrder = $request->query('sort_order', 'desc');

            // Xác thực và điều chỉnh tham số
            $perPage = min(max((int) $perPage, 1), 100);
            $page = max((int) $page, 1);
            $sortField = in_array($sortField, ['title', 'start_time', 'end_time', 'created_at', 'updated_at'])
                ? $sortField
                : 'start_time';
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            // Lấy danh sách sự kiện đã ghim
            $query = Event::with([
                'creator',
                'locations',
                'attachments',
                'preparers',
                'markedByUsers' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }
            ])
                ->whereHas('markedByUsers', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('is_marked', true);
                });

            // Áp dụng sắp xếp
            $query->orderBy($sortField, $sortOrder);

            // Lấy kết quả có phân trang
            $events = $query->paginate($perPage, ['*'], 'page', $page);

            // Xử lý dữ liệu trả về
            $eventsData = $events->getCollection()->map(function ($event) use ($user) {
                $eventData = $event->toArray();

                // Xử lý các thông tin khác nếu cần

                // Thêm thông tin đánh dấu của người dùng
                $markedByUser = $event->markedByUsers->first();
                $eventData['is_marked'] = $markedByUser ? $markedByUser->pivot->is_marked : false;
                $eventData['is_viewed'] = $markedByUser ? $markedByUser->pivot->is_viewed : false;

                // Loại bỏ thông tin không cần thiết
                unset($eventData['markedByUsers']);

                return $eventData;
            });

            return $this->sendSuccess([
                'events' => $eventsData,
                'page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'message' => 'Danh sách sự kiện đã ghim đã được lấy thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sự kiện đã ghim:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách sự kiện đã ghim: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
