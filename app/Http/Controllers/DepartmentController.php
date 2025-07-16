<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

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
            $perPage = $request->query('per_page', 200); // Mặc định 10 bản ghi mỗi trang
            $page = $request->query('page', 1); // Mặc định trang 1
            $sortField = $request->query('sort_field', 'created_at'); // Mặc định sắp xếp theo created_at
            $sortOrder = $request->query('sort_order', 'desc'); // Mặc định sắp xếp giảm dần
            $searchName = $request->query('search', ''); // Tìm kiếm theo tên

            // Xác thực tham số
            $perPage = min(max((int) $perPage, 1), 200); // Giới hạn per_page từ 1 đến 100
            $page = max((int) $page, 1); // Đảm bảo page >= 1
            $sortField = in_array($sortField, ['name', 'created_at', 'updated_at'])
                ? $sortField
                : 'name'; // Chỉ cho phép các trường hợp lệ
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            $query = Department::active();

            // Áp dụng tìm kiếm theo tên nếu có
            if (!empty($searchName)) {
                $query->byName($searchName);
            }

            // Áp dụng sắp xếp và phân trang
            $departments = $query->orderBy($sortField, $sortOrder)
                ->paginate($perPage, ['*'], 'page', $page);

            // Trả về phản hồi JSON
            return $this->sendSuccess([
                'departments' => $departments->items(), // Danh sách phòng ban
                'page' => $departments->currentPage(), // Trang hiện tại
                'per_page' => $departments->perPage(), // Số bản ghi mỗi trang
                'total' => $departments->total(), // Tổng số bản ghi
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'message' => 'Danh sách phòng ban đã được lấy thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách phòng ban:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách phòng ban.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Lấy thông tin department theo ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Tìm phòng ban
            $department = Department::active()->with(['events'])->find($id);
            if (!$department) {
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendSuccess([
                'data' => $department,
                'message' => 'Lấy thông tin phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin phòng ban:', [
                'department_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy thông tin phòng ban.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tạo mới department
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('create_departments')) {
                return $this->sendError('Bạn không có quyền tạo phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:departments,name',
                'description' => 'nullable|string',
            ]);

            // Tạo phòng ban trong transaction
            $department = DB::transaction(function () use ($validated, $user) {
                return Department::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'creator_id' => $user->id,
                ]);
            });

            // Ghi log
            Log::info('Phòng ban đã được tạo:', [
                'department_id' => $department->id,
                'name' => $department->name,
                'user_id' => $user->id,
                'created_at' => now()->toDateTimeString(),
            ]);

            return $this->sendSuccess([
                'data' => $department,
                'message' => 'Tạo phòng ban thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo phòng ban:', [
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi tạo phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật department
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('edit_departments')) {
                return $this->sendError('Bạn không có quyền chỉnh sửa phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:departments,name,' . $id,
                'description' => 'nullable|string',
            ]);

            // Cập nhật phòng ban trong transaction
            $department = DB::transaction(function () use ($department, $validated) {
                $department->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                ]);
                return $department->refresh();
            });

            // Ghi log
            Log::info('Phòng ban đã được cập nhật:', [
                'department_id' => $department->id,
                'name' => $department->name,
                'user_id' => $user->id,
                'updated_at' => now()->toDateTimeString(),
            ]);

            return $this->sendSuccess([
                'data' => $department,
                'message' => 'Cập nhật phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật phòng ban:', [
                'department_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi cập nhật phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Xóa department
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('delete_departments')) {
                return $this->sendError('Bạn không có quyền xóa phòng ban.', Response::HTTP_FORBIDDEN);
            }

            // Tìm phòng ban
            $department = Department::active()->find($id);
            if (!$department) {
                return $this->sendError('Phòng ban không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Kiểm tra quan hệ với sự kiện
            $hasEvents = DB::table('event_preparers')
                ->where('department_id', $department->id)
                ->exists();
            if ($hasEvents) {
                return $this->sendError('Không thể xóa phòng ban vì nó đang liên kết với các sự kiện.', Response::HTTP_BAD_REQUEST);
            }

            // Xóa phòng ban trong transaction
            DB::transaction(function () use ($department) {
                $department->delete(); // Hỗ trợ soft delete
            });

            return $this->sendSuccess([
                'id' => $id,
                'name' => $department->name,
                'message' => 'Xóa phòng ban thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa phòng ban:', [
                'department_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi xóa phòng ban: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
