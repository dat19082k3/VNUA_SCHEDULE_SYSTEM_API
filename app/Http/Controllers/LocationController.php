<?php

namespace App\Http\Controllers;

use App\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use App\Models\Location;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Xác thực người dùng qua Bearer Token
     */
    private function authenticateUser(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) {
            Log::warning('No Bearer Token provided', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
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
     * GET /api/locations
     *
     * Lấy danh sách địa điểm
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lấy tham số phân trang, sắp xếp và tìm kiếm từ request
            $perPage = $request->query('per_page', 200); // Mặc định 200 bản ghi mỗi trang
            $page = $request->query('page', 1); // Mặc định trang 1
            $sortField = $request->query('sort_field', 'created_at'); // Mặc định sắp xếp theo created_at
            $sortOrder = $request->query('sort_order', 'desc'); // Mặc định sắp xếp giảm dần
            $searchName = $request->query('search', ''); // Tìm kiếm theo tên

            // Xác thực tham số
            $perPage = min(max((int) $perPage, 1), 200); // Giới hạn per_page từ 1 đến 200
            $page = max((int) $page, 1); // Đảm bảo page >= 1
            $sortField = in_array($sortField, ['name', 'created_at', 'updated_at'])
                ? $sortField
                : 'name'; // Chỉ cho phép các trường hợp lệ
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            $query = Location::active();

            // Áp dụng tìm kiếm theo tên nếu có
            if (!empty($searchName)) {
                $query->byName($searchName);
            }

            // Áp dụng sắp xếp và phân trang
            $locations = $query->orderBy($sortField, $sortOrder)
                ->paginate($perPage, ['*'], 'page', $page);

            // Trả về phản hồi JSON
            return $this->sendSuccess([
                'locations' => $locations->items(), // Danh sách địa điểm
                'page' => $locations->currentPage(), // Trang hiện tại
                'per_page' => $locations->perPage(), // Số bản ghi mỗi trang
                'total' => $locations->total(), // Tổng số bản ghi
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'message' => 'Danh sách địa điểm đã được lấy thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách địa điểm:', [
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách địa điểm.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/locations/{location}
     *
     * Lấy thông tin địa điểm theo ID
     *
     * @return JsonResponse
     */
    public function show(Request $request,$id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('view_locations')) {
                return $this->sendError('Bạn không có quyền xem địa điểm.', Response::HTTP_FORBIDDEN);
            }

            // Tìm địa điểm
            $locationData = Location::active()->with(['events'])->find($id);
            if (!$locationData) {
                return $this->sendError('Địa điểm không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Ghi log
            Log::info('Lấy thông tin địa điểm:', [
                'location_id' => $locationData->id,
                'name' => $locationData->name,
                'user_id' => $user->id,
            ]);

            return $this->sendSuccess([
                'data' => $locationData,
                'message' => 'Lấy thông tin địa điểm thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy thông tin địa điểm:', [
                'location_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy thông tin địa điểm.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/locations
     *
     * Tạo mới địa điểm
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('create_locations')) {
                return $this->sendError('Bạn không có quyền tạo địa điểm.', Response::HTTP_FORBIDDEN);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:locations,name',
                'description' => 'nullable|string',
            ]);

            // Tạo địa điểm trong transaction
            $location = DB::transaction(function () use ($validated, $user) {
                return Location::create([
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'creator_id' => $user->id,
                ]);
            });

            // Ghi log
            Log::info('Địa điểm đã được tạo:', [
                'location_id' => $location->id,
                'name' => $location->name,
                'user_id' => $user->id,
                'created_at' => now()->toDateTimeString(),
            ]);

            return $this->sendSuccess([
                'data' => $location,
                'message' => 'Tạo địa điểm thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo địa điểm:', [
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi tạo địa điểm: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * PUT /api/locations/{location}
     *
     * Cập nhật địa điểm
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('edit_locations')) {
                return $this->sendError('Bạn không có quyền chỉnh sửa địa điểm.', Response::HTTP_FORBIDDEN);
            }

            // Tìm địa điểm
            $locationData = Location::active()->find($id);
            if (!$locationData) {
                return $this->sendError('Địa điểm không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Xác thực dữ liệu
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:locations,name,' . $id,
                'description' => 'nullable|string',
            ]);

            // Cập nhật địa điểm trong transaction
            $locationData = DB::transaction(function () use ($locationData, $validated) {
                $locationData->update([
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null,
                ]);
                return $locationData->refresh();
            });

            // Ghi log
            Log::info('Địa điểm đã được cập nhật:', [
                'location_id' => $locationData->id,
                'name' => $locationData->name,
                'user_id' => $user->id,
                'updated_at' => now()->toDateTimeString(),
            ]);

            return $this->sendSuccess([
                'data' => $locationData,
                'message' => 'Cập nhật địa điểm thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật địa điểm:', [
                'location_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi cập nhật địa điểm: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * DELETE /api/locations/{location}
     *
     * Xóa địa điểm
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user || !$user->hasPermissionTo('delete_locations')) {
                return $this->sendError('Bạn không có quyền xóa địa điểm.', Response::HTTP_FORBIDDEN);
            }

            // Tìm địa điểm
            $locationData = Location::active()->find($id);
            if (!$locationData) {
                return $this->sendError('Địa điểm không tồn tại hoặc đã bị xóa.', Response::HTTP_NOT_FOUND);
            }

            // Kiểm tra quan hệ với sự kiện
            $hasEvents = DB::table('event_locations')
                ->where('location_id', $locationData->id)
                ->join('events', 'event_locations.event_id', '=', 'events.id')
                ->whereNull('events.deleted_at')
                ->exists();
            if ($hasEvents) {
                return $this->sendError('Không thể xóa địa điểm vì nó đang liên kết với các sự kiện.', Response::HTTP_BAD_REQUEST);
            }

            // Xóa địa điểm trong transaction
            DB::transaction(function () use ($locationData) {
                $locationData->delete(); // Hỗ trợ soft delete
            });

            // Ghi log
            Log::info('Địa điểm đã được xóa:', [
                'location_id' => $id,
                'name' => $locationData->name,
                'user_id' => $user->id,
                'deleted_at' => now()->toDateTimeString(),
            ]);

            return $this->sendSuccess([
                'id' => $id,
                'name' => $locationData->name,
                'message' => 'Xóa địa điểm thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa địa điểm:', [
                'location_id' => $id,
                'user_id' => $user?->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi xóa địa điểm: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
