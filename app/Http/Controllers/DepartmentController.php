<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Department\DepartmentStoreRequest;
use App\Http\Requests\Department\DepartmentUpdateRequest;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class DepartmentController extends Controller
{

    private function authenticateUser(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            Log::warning('No Bearer Token provided', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
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

    /**
     * Lấy tất cả department
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy tham số phân trang, sắp xếp và tìm kiếm từ request
            $perPage = $request->query('per_page'); // Có thể null để lấy tất cả
            $page = $request->query('page', 1); // Mặc định trang 1
            $sortField = $request->query('sort_field', 'name'); // Mặc định sắp xếp theo name
            $sortOrder = $request->query('sort_order', 'asc'); // Mặc định sắp xếp tăng dần
            $searchName = $request->query('search', ''); // Tìm kiếm theo tên
            $withEvents = $request->query('with_events', false); // Có bao gồm sự kiện không
            $withUsers = $request->query('with_users', false); // Có bao gồm người dùng không
            $withPrimaryUsers = $request->query('with_primary_users', false); // Có bao gồm người dùng chính không

            // Log request parameters for debugging
            Log::info('Yêu cầu lấy danh sách phòng ban:', [
                'per_page' => $perPage,
                'page' => $page,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'search' => $searchName,
                'with_events' => $withEvents,
                'with_users' => $withUsers,
                'with_primary_users' => $withPrimaryUsers,
            ]);

            // Xác thực tham số
            if ($perPage !== null) {
                $perPage = min(max((int) $perPage, 1), 200); // Giới hạn per_page từ 1 đến 200
            }
            $page = max((int) $page, 1); // Đảm bảo page >= 1
            $sortField = in_array($sortField, ['name', 'created_at', 'updated_at', 'id'])
                ? $sortField
                : 'name'; // Chỉ cho phép các trường hợp hợp lệ
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';
            $withEvents = filter_var($withEvents, FILTER_VALIDATE_BOOLEAN);
            $withUsers = filter_var($withUsers, FILTER_VALIDATE_BOOLEAN);
            $withPrimaryUsers = filter_var($withPrimaryUsers, FILTER_VALIDATE_BOOLEAN);

            // Xác định các quan hệ cần eager load
            $relations = [];
            if ($withEvents) {
                $relations[] = 'preparedEvents';
            }
            if ($withUsers) {
                $relations[] = 'users';
            }
            if ($withPrimaryUsers) {
                $relations[] = 'primaryUsers';
            }

            // Khởi tạo truy vấn
            $query = Department::active()->with($relations);

            // Áp dụng tìm kiếm theo tên nếu có
            if (!empty($searchName)) {
                $query->byName($searchName);
            }

            // Xử lý phân trang hoặc lấy tất cả
            if (is_null($perPage)) {
                // Lấy tất cả phòng ban
                $allDepartments = $query->orderBy($sortField, $sortOrder)->get();
                $total = $allDepartments->count();

                return $this->sendSuccess([
                    'departments' => $allDepartments,
                    'page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                    'last_page' => 1,
                    'sort_field' => $sortField,
                    'sort_order' => $sortOrder,
                    'message' => 'Tất cả phòng ban đã được lấy thành công.',
                ], Response::HTTP_OK);
            } else {
                // Áp dụng sắp xếp và phân trang
                $departments = $query->orderBy($sortField, $sortOrder)
                    ->paginate($perPage, ['*'], 'page', $page);

                // Trả về phản hồi JSON với phân trang
                return $this->sendSuccess([
                    'departments' => $departments->items(),
                    'page' => $departments->currentPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                    'last_page' => $departments->lastPage(),
                    'sort_field' => $sortField,
                    'sort_order' => $sortOrder,
                    'message' => 'Danh sách phòng ban đã được lấy thành công.',
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách phòng ban:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Lấy thông tin department theo ID
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            // Xác định các quan hệ cần eager load từ request
            $withEvents = filter_var($request->query('with_events', true), FILTER_VALIDATE_BOOLEAN);
            $withUsers = filter_var($request->query('with_users', true), FILTER_VALIDATE_BOOLEAN);
            $withPrimaryUsers = filter_var($request->query('with_primary_users', true), FILTER_VALIDATE_BOOLEAN);

            // Log request parameters for debugging
            Log::info('Yêu cầu lấy thông tin chi tiết phòng ban:', [
                'department_id' => $id,
                'with_events' => $withEvents,
                'with_users' => $withUsers,
                'with_primary_users' => $withPrimaryUsers,
            ]);

            // Xác định các quan hệ cần eager load
            $relations = [];
            if ($withEvents) {
                $relations[] = 'preparedEvents';
            }
            if ($withUsers) {
                $relations[] = 'users';
            }
            if ($withPrimaryUsers) {
                $relations[] = 'primaryUsers';
            }

            // Tìm phòng ban với các quan hệ đã chọn
            $department = Department::active()->with($relations)->find($id);
            if (!$department) {
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Thêm thông tin thống kê
            $stats = [
                'users_count' => $withUsers ? $department->users->count() : $department->users()->count(),
                'primary_users_count' => $withPrimaryUsers ? $department->primaryUsers->count() : $department->primaryUsers()->count(),
                'prepared_events_count' => $withEvents ? $department->preparedEvents->count() : $department->preparedEvents()->count(),
            ];

            return $this->sendSuccess([
                'department' => $department,
                'stats' => $stats,
                'message' => 'Lấy thông tin phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin phòng ban:', [
                'department_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy thông tin phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tạo mới department
     */
    public function store(DepartmentStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                DB::rollBack();
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền tạo phòng ban
            if (!$user->hasPermissionTo('create_departments')) {
                DB::rollBack();
                return $this->sendError('Bạn không có quyền tạo phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Lấy dữ liệu đã được xác thực
            $validated = $request->validated();

            // Log request data for debugging
            Log::info('Tạo phòng ban mới - dữ liệu nhận được:', [
                'user_id' => $user->id,
                'validated_data' => $validated,
            ]);

            // Kiểm tra tên phòng ban đã tồn tại chưa
            $existingDepartment = Department::where('name', $validated['name'])->withTrashed()->first();
            if ($existingDepartment && $existingDepartment->deleted_at) {
                // Nếu phòng ban đã bị xóa mềm (soft delete), khôi phục
                $existingDepartment->restore();
                $existingDepartment->update([
                    'description' => $validated['description'] ?? $existingDepartment->description,
                ]);

                Log::info('Phòng ban đã bị xóa trước đó đã được khôi phục:', [
                    'department_id' => $existingDepartment->id,
                    'name' => $existingDepartment->name,
                    'user_id' => $user->id,
                ]);

                DB::commit();
                return $this->sendSuccess([
                    'department' => $existingDepartment,
                    'message' => 'Phòng ban đã tồn tại trước đó và đã được khôi phục.',
                ], Response::HTTP_OK);
            }

            // Tạo phòng ban mới
            try {
                $department = Department::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'creator_id' => $user->id,
                ]);

                Log::info('Đã tạo phòng ban mới:', [
                    'department_id' => $department->id,
                    'name' => $department->name,
                    'creator_id' => $user->id,
                    'created_at' => now()->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi tạo phòng ban:', [
                    'error' => $e->getMessage(),
                    'data' => $validated
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi tạo phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Commit transaction
            DB::commit();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'department' => $department,
                'message' => 'Phòng ban đã được tạo thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi validation khi tạo phòng ban:', [
                'errors' => $e->validator->errors()->toArray(),
                'user_id' => $user->id ?? null,
            ]);

            return $this->sendError('Dữ liệu không hợp lệ: ' . json_encode($e->validator->errors()->toArray()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi tạo phòng ban:', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi tạo phòng ban. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Cập nhật department
     */
    public function update(DepartmentUpdateRequest $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                DB::rollBack();
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền chỉnh sửa phòng ban
            if (!$user->hasPermissionTo('edit_departments')) {
                DB::rollBack();
                return $this->sendError('Bạn không có quyền chỉnh sửa phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                DB::rollBack();
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Lấy dữ liệu đã được xác thực
            $validated = $request->validated();

            // Log request data for debugging
            Log::info('Cập nhật phòng ban - dữ liệu nhận được:', [
                'department_id' => $id,
                'user_id' => $user->id,
                'validated_data' => $validated,
            ]);

            // Kiểm tra xem tên mới có bị trùng với phòng ban khác không
            if ($validated['name'] !== $department->name) {
                $existingDepartment = Department::where('name', $validated['name'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingDepartment) {
                    DB::rollBack();
                    return $this->sendError('Tên phòng ban đã tồn tại trong hệ thống.', Response::HTTP_CONFLICT);
                }
            }

            // Lưu lại thông tin cũ để ghi log
            $oldName = $department->name;
            $oldDescription = $department->description;

            // Cập nhật phòng ban
            try {
                $department->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'updated_at' => now(),
                ]);

                // Ghi log các thay đổi
                $changes = [];
                if ($oldName !== $validated['name']) {
                    $changes['name'] = ['old' => $oldName, 'new' => $validated['name']];
                }
                if ($oldDescription !== ($validated['description'] ?? null)) {
                    $changes['description'] = ['old' => $oldDescription, 'new' => $validated['description'] ?? null];
                }

                Log::info('Phòng ban đã được cập nhật:', [
                    'department_id' => $department->id,
                    'name' => $department->name,
                    'user_id' => $user->id,
                    'changes' => $changes,
                    'updated_at' => now()->toDateTimeString(),
                ]);

            } catch (\Exception $e) {
                Log::error('Lỗi khi cập nhật phòng ban:', [
                    'department_id' => $id,
                    'error' => $e->getMessage(),
                    'data' => $validated
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi cập nhật phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Load các mối quan hệ liên quan
            $department->load(['users', 'primaryUsers', 'preparedEvents']);

            // Commit transaction
            DB::commit();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'department' => $department,
                'message' => 'Cập nhật phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi validation khi cập nhật phòng ban:', [
                'errors' => $e->validator->errors()->toArray(),
                'department_id' => $id,
                'user_id' => $user->id ?? null,
            ]);

            return $this->sendError('Dữ liệu không hợp lệ: ' . json_encode($e->validator->errors()->toArray()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi cập nhật phòng ban:', [
                'department_id' => $id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi cập nhật phòng ban. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Xóa department
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                DB::rollBack();
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền xóa phòng ban
            if (!$user->hasPermissionTo('delete_departments')) {
                DB::rollBack();
                return $this->sendError('Bạn không có quyền xóa phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                DB::rollBack();
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Log request for debugging
            Log::info('Yêu cầu xóa phòng ban:', [
                'department_id' => $id,
                'department_name' => $department->name,
                'user_id' => $user->id,
            ]);

            // Kiểm tra các ràng buộc trước khi xóa

            // 1. Kiểm tra xem phòng ban có đang là đơn vị chuẩn bị cho sự kiện nào không
            $preparedEventsCount = DB::table('event_preparers')
                ->where('department_id', $department->id)
                ->count();

            if ($preparedEventsCount > 0) {
                DB::rollBack();
                return $this->sendError(
                    'Không thể xóa phòng ban vì nó đang là đơn vị chuẩn bị cho ' . $preparedEventsCount . ' sự kiện. Vui lòng loại bỏ phòng ban khỏi các sự kiện trước.',
                    Response::HTTP_CONFLICT
                );
            }

            // 2. Kiểm tra xem có người dùng nào đang có phòng ban này là phòng ban chính không
            $primaryUsersCount = User::where('primary_department_id', $department->id)->count();
            if ($primaryUsersCount > 0) {
                DB::rollBack();
                return $this->sendError(
                    'Không thể xóa phòng ban vì có ' . $primaryUsersCount . ' người dùng đang sử dụng nó làm phòng ban chính. Vui lòng thay đổi phòng ban chính của các người dùng này trước.',
                    Response::HTTP_CONFLICT
                );
            }

            // 3. Kiểm tra xem có người dùng nào thuộc phòng ban này không (quan hệ nhiều-nhiều)
            $usersCount = $department->users()->count();

            // Lưu thông tin phòng ban để trả về sau khi xóa
            $departmentInfo = [
                'id' => $department->id,
                'name' => $department->name,
            ];

            // Xóa các mối quan hệ trước khi xóa phòng ban
            try {
                // Xóa mối quan hệ với người dùng
                if ($usersCount > 0) {
                    $department->users()->detach();
                    Log::info('Đã xóa mối quan hệ với người dùng:', [
                        'department_id' => $id,
                        'users_count' => $usersCount,
                    ]);
                }

                // Kiểm tra xem phòng ban có xuất hiện trong danh sách participants của sự kiện nào không
                $participantEvents = Event::whereJsonContains('participants', ['type' => 'department', 'id' => $id])->get();
                foreach ($participantEvents as $event) {
                    $participants = json_decode(json_encode($event->participants), true);
                    if (is_array($participants)) {
                        $filteredParticipants = array_filter($participants, function($participant) use ($id) {
                            return !($participant['type'] === 'department' && $participant['id'] == $id);
                        });
                        $event->participants = array_values($filteredParticipants);
                    }
                    $event->save();

                    Log::info('Đã xóa phòng ban khỏi danh sách người tham gia của sự kiện:', [
                        'department_id' => $id,
                        'event_id' => $event->id,
                    ]);
                }

                // Thực hiện soft delete
                $department->delete();

                Log::info('Phòng ban đã được xóa thành công:', [
                    'department_id' => $id,
                    'name' => $departmentInfo['name'],
                    'user_id' => $user->id,
                    'deleted_at' => now()->toDateTimeString(),
                ]);

            } catch (\Exception $e) {
                Log::error('Lỗi khi xóa phòng ban:', [
                    'department_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi xóa phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Commit transaction
            DB::commit();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'department' => $departmentInfo,
                'message' => 'Xóa phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi xóa phòng ban:', [
                'department_id' => $id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi xóa phòng ban. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Thêm người dùng vào phòng ban
     */
    public function assignUsers(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                DB::rollBack();
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền chỉnh sửa phòng ban
            if (!$user->hasPermissionTo('edit_departments')) {
                DB::rollBack();
                return $this->sendError('Bạn không có quyền thêm người dùng vào phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'required|integer|exists:users,id',
                'is_primary' => 'boolean',
            ]);

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                DB::rollBack();
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Log request data for debugging
            Log::info('Yêu cầu thêm người dùng vào phòng ban:', [
                'department_id' => $id,
                'department_name' => $department->name,
                'user_ids' => $validated['user_ids'],
                'is_primary' => $validated['is_primary'] ?? false,
                'requestor_id' => $user->id,
            ]);

            $isPrimary = $validated['is_primary'] ?? false;
            $userIds = $validated['user_ids'];
            $usersAdded = 0;

            try {
                if ($isPrimary) {
                    // Cập nhật primary_department_id cho các user
                    foreach ($userIds as $userId) {
                        $userToUpdate = User::find($userId);
                        if ($userToUpdate) {
                            $userToUpdate->update(['primary_department_id' => $id]);
                            $usersAdded++;
                        }
                    }

                    Log::info('Đã cập nhật phòng ban chính cho người dùng:', [
                        'department_id' => $id,
                        'user_count' => $usersAdded,
                    ]);
                } else {
                    // Thêm users vào mối quan hệ many-to-many
                    $department->users()->syncWithoutDetaching($userIds);
                    $usersAdded = count($userIds);

                    Log::info('Đã thêm người dùng vào phòng ban:', [
                        'department_id' => $id,
                        'user_count' => $usersAdded,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Lỗi khi thêm người dùng vào phòng ban:', [
                    'department_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi thêm người dùng vào phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Load các mối quan hệ để trả về
            $department->load(['users', 'primaryUsers']);

            // Commit transaction
            DB::commit();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'department' => $department,
                'users_added' => $usersAdded,
                'is_primary' => $isPrimary,
                'message' => 'Đã thêm ' . $usersAdded . ' người dùng vào phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi validation khi thêm người dùng vào phòng ban:', [
                'errors' => $e->validator->errors()->toArray(),
                'department_id' => $id,
                'user_id' => $user->id ?? null,
            ]);

            return $this->sendError('Dữ liệu không hợp lệ: ' . json_encode($e->validator->errors()->toArray()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi thêm người dùng vào phòng ban:', [
                'department_id' => $id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi thêm người dùng vào phòng ban. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Loại bỏ người dùng khỏi phòng ban
     */
    public function removeUsers(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                DB::rollBack();
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            // Kiểm tra quyền chỉnh sửa phòng ban
            if (!$user->hasPermissionTo('edit_departments')) {
                DB::rollBack();
                return $this->sendError('Bạn không có quyền loại bỏ người dùng khỏi phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'required|integer|exists:users,id',
                'is_primary' => 'boolean',
            ]);

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                DB::rollBack();
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Log request data for debugging
            Log::info('Yêu cầu loại bỏ người dùng khỏi phòng ban:', [
                'department_id' => $id,
                'department_name' => $department->name,
                'user_ids' => $validated['user_ids'],
                'is_primary' => $validated['is_primary'] ?? false,
                'requestor_id' => $user->id,
            ]);

            $isPrimary = $validated['is_primary'] ?? false;
            $userIds = $validated['user_ids'];
            $usersRemoved = 0;

            try {
                if ($isPrimary) {
                    // Loại bỏ phòng ban chính (set về null)
                    foreach ($userIds as $userId) {
                        $userToUpdate = User::where('id', $userId)
                            ->where('primary_department_id', $id)
                            ->first();
                        if ($userToUpdate) {
                            $userToUpdate->update(['primary_department_id' => null]);
                            $usersRemoved++;
                        }
                    }

                    Log::info('Đã loại bỏ phòng ban chính cho người dùng:', [
                        'department_id' => $id,
                        'user_count' => $usersRemoved,
                    ]);
                } else {
                    // Loại bỏ users khỏi mối quan hệ many-to-many
                    $department->users()->detach($userIds);
                    $usersRemoved = count($userIds);

                    Log::info('Đã loại bỏ người dùng khỏi phòng ban:', [
                        'department_id' => $id,
                        'user_count' => $usersRemoved,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Lỗi khi loại bỏ người dùng khỏi phòng ban:', [
                    'department_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                DB::rollBack();
                return $this->sendError('Lỗi khi loại bỏ người dùng khỏi phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Load các mối quan hệ để trả về
            $department->load(['users', 'primaryUsers']);

            // Commit transaction
            DB::commit();

            // Trả về phản hồi thành công
            return $this->sendSuccess([
                'department' => $department,
                'users_removed' => $usersRemoved,
                'is_primary' => $isPrimary,
                'message' => 'Đã loại bỏ ' . $usersRemoved . ' người dùng khỏi phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Lỗi validation khi loại bỏ người dùng khỏi phòng ban:', [
                'errors' => $e->validator->errors()->toArray(),
                'department_id' => $id,
                'user_id' => $user->id ?? null,
            ]);

            return $this->sendError('Dữ liệu không hợp lệ: ' . json_encode($e->validator->errors()->toArray()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi không xác định khi loại bỏ người dùng khỏi phòng ban:', [
                'department_id' => $id,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi loại bỏ người dùng khỏi phòng ban. Vui lòng thử lại sau hoặc liên hệ quản trị viên.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Lấy tất cả phòng ban không phân trang
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDepartments(Request $request): JsonResponse
    {
        try {
            // Lấy tham số sắp xếp và tìm kiếm từ request
            $sortField = $request->query('sort_field', 'name'); // Mặc định sắp xếp theo name
            $sortOrder = $request->query('sort_order', 'asc'); // Mặc định sắp xếp tăng dần
            $searchName = $request->query('search', ''); // Tìm kiếm theo tên
            $withEvents = filter_var($request->query('with_events', false), FILTER_VALIDATE_BOOLEAN);
            $withUsers = filter_var($request->query('with_users', false), FILTER_VALIDATE_BOOLEAN);
            $withPrimaryUsers = filter_var($request->query('with_primary_users', false), FILTER_VALIDATE_BOOLEAN);

            // Xác thực tham số
            $sortField = in_array($sortField, ['name', 'created_at', 'updated_at', 'id'])
                ? $sortField
                : 'name'; // Chỉ cho phép các trường hợp hợp lệ
            $sortOrder = in_array($sortOrder, ['asc', 'desc'])
                ? $sortOrder
                : 'asc'; // Chỉ cho phép asc hoặc desc

            // Khởi tạo truy vấn
            $query = Department::query();

            // Áp dụng các mối quan hệ
            $with = [];
            if ($withEvents) {
                $with[] = 'preparedEvents';
            }
            if ($withUsers) {
                $with[] = 'users';
            }
            if ($withPrimaryUsers) {
                $with[] = 'primaryUsers';
            }

            if (!empty($with)) {
                $query->with($with);
            }

            // Áp dụng tìm kiếm theo tên nếu có
            if (!empty($searchName)) {
                $query->where('name', 'like', "%{$searchName}%");
            }

            // Lấy tất cả phòng ban
            $allDepartments = $query->orderBy($sortField, $sortOrder)->get();

            // Thêm số lượng thống kê
            $departmentsWithCounts = $allDepartments->map(function ($department) {
                $data = $department->toArray();

                // Thêm số lượng thống kê nếu không đã được tải quan hệ
                if (!isset($data['users_count']) && !isset($data['users'])) {
                    $data['users_count'] = $department->users()->count();
                }

                if (!isset($data['primary_users_count']) && !isset($data['primary_users'])) {
                    $data['primary_users_count'] = $department->primaryUsers()->count();
                }

                if (!isset($data['events_count']) && !isset($data['prepared_events'])) {
                    $data['events_count'] = $department->preparedEvents()->count();
                }

                return $data;
            });

            $total = $allDepartments->count();

            return $this->sendSuccess([
                'departments' => $departmentsWithCounts,
                'page' => 1,
                'per_page' => $total,
                'total' => $total,
                'last_page' => 1,
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'message' => 'Tất cả phòng ban đã được lấy thành công.',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy tất cả phòng ban:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendError(
                'Đã xảy ra lỗi khi lấy tất cả phòng ban: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
