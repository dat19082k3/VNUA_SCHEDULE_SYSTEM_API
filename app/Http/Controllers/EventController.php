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
            $query = Event::with(['locations', 'attachments', 'preparers']);

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
                        $query->where(function ($q) use ($user) {
                            $q->where('creator_id', $user->id);
                        });
                    } else {
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
                $total = $events->count();
                $page = max((int) $page, 1);
                $perPage = $total > 0 ? $total : 1;
                $lastPage = $total > 0 ? 1 : 0;

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

                return $this->sendSuccess([
                    'events' => $events->items(),
                    'page' => $events->currentPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
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
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Lấy dữ liệu đã được xác thực
            $validated = $request->validated();

            // Xử lý location_ids
            $locationIds = $validated['location_ids'] ?? [];
            $hasZeroLocation = in_array(0, $locationIds);
            // Loại bỏ ID = 0 trước khi kiểm tra hợp lệ
            $locationIdsToValidate = array_filter($locationIds, fn($id) => $id !== 0);

            // Xử lý custom_location nếu có ID = 0
            if ($hasZeroLocation && !empty($validated['custom_locations'])) {
                if (!$user->hasPermissionTo('create_locations')) {
                    return $this->sendError('Bạn không có quyền tạo địa điểm mới.', Response::HTTP_FORBIDDEN);
                }
                // Tách chuỗi custom_location thành mảng
                $locationNames = array_map('trim', explode(',', $validated['custom_locations']));
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

            // Loại bỏ ID = 0 trước khi kiểm tra hợp lệ
            $preparerIdsToValidate = array_filter($preparerIds, fn($id) => $id !== 0);

            // Xử lý customer_preparer nếu có ID = 0
            if ($hasZeroPreparer && !empty($validated['custom_preparers'])) {
                if (!$user->hasPermissionTo('create_departments')) {
                    return $this->sendError('Bạn không có quyền tạo phòng ban mới.', Response::HTTP_FORBIDDEN);
                }
                // Tách chuỗi customer_preparer thành mảng
                $preparerNames = array_map('trim', explode(',', $validated['custom_preparers']));
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
                return $this->sendError('Phải có ít nhất một đơn vị chuẩn bị hợp lệ.', Response::HTTP_BAD_REQUEST);
            }
            // Tạo sự kiện mới
            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'host' => $validated['host'],
                'participants' => $validated['participants'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'reminder_type' => $validated['reminder_type'] ?? 'none',
                'reminder_time' => $validated['reminder_time'] ?? null,
                'creator_id' => $user->id,
            ]);

            // Đồng bộ địa điểm
            $event->locations()->sync($validLocationIds);

            // Đồng bộ preparers
            $event->preparers()->sync($validPreparerIds);

            // Liên kết các tệp đính kèm (nếu có)
            if (!empty($validated['attachments'])) {
                $validAttachmentIds = Attachment::whereIn('id', $validated['attachments'])
                    ->where('uploader_id', $user->id)
                    ->pluck('id')
                    ->toArray();

                if (count($validAttachmentIds) !== count($validated['attachments'])) {
                    return $this->sendError('Một hoặc nhiều tệp đính kèm không hợp lệ hoặc bạn không có quyền.', Response::HTTP_FORBIDDEN);
                }

                $event->attachments()->sync($validAttachmentIds);
            }

            // Tải các mối quan hệ để trả về
            $event->load(['locations', 'attachments', 'preparers']);

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'data' => $event,
                'message' => 'Sự kiện đã được tạo thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo sự kiện:', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi tạo sự kiện.', Response::HTTP_INTERNAL_SERVER_ERROR);
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
                    'host' => $validated['host'],
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
                                'old_value' => $oldValue,
                                'new_value' => $newValue,
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
                    'host' => $validated['host'],
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
            $event->load(['locations', 'preparers', 'attachments']);

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

            $eventData = Event::find($id);
            if (!$eventData) {
                return $this->sendError('Sự kiện không tồn tại.', Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'status' => 'required|in:pending,approved,declined',
            ]);

            $eventData = DB::transaction(function () use ($eventData, $validated, $user) {
                $oldStatus = $eventData->status;

                if ($oldStatus !== $validated['status']) {
                    EventHistory::create([
                        'event_id' => $eventData->id,
                        'user_id' => $user->id,
                        'field_name' => 'status',
                        'old_value' => $oldStatus,
                        'new_value' => $validated['status'],
                    ]);
                }

                $eventData->update([
                    'status' => $validated['status'],
                ]);

                return $eventData->refresh();
            });

            Log::info('Sự kiện đã được phê duyệt/từ chối:', [
                'event_id' => $eventData->id,
                'name' => $eventData->title, // Changed from 'name' to 'title' to match schema
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
}
