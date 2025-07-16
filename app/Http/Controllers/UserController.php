<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
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

    /**
     * Retrieve a paginated list of users with their roles and department.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Xác thực người dùng
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $sortField = $request->query('sort_field', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');
            $search = $request->query('search');

            $perPage = min(max((int) $perPage, 1), 100);
            $page = max((int) $page, 1);
            $sortField = in_array($sortField, ['user_name', 'email', 'first_name', 'last_name', 'created_at', 'updated_at'])
                ? $sortField
                : 'created_at';
            $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            $query = User::with(['department', 'roles']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ['%' . $search . '%']);
                });
            }

            $users = $query->orderBy($sortField, $sortOrder)
                ->paginate($perPage, ['*'], 'page', $page);

            $userData = $users->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_name' => $user->user_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role_sso' => $user->role_sso,
                    'status' => $user->status,
                    'code' => $user->code,
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'name' => $user->department->name,
                    ] : null,
                    'protected' => $user->protected,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ];
            });

            // Return JSON response
            return $this->sendSuccess([
                'users' => $userData,
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'sort_field' => $sortField,
                'sort_order' => $sortOrder,
                'message' => 'Danh sách người dùng đã được lấy thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách người dùng:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách người dùng.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign roles to a user.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignRole(Request $request, $userId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,name',
            ]);

            $user = User::findOrFail($userId);

            if ($user->protected) {
                return $this->sendError('Không thể thay đổi vai trò của người dùng được bảo vệ.', Response::HTTP_FORBIDDEN);
            }

            $user->syncRoles($request->roles);

            return $this->sendSuccess([
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name'),
                'message' => 'Cấp vai trò cho người dùng thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cấp vai trò cho người dùng:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi cấp vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign permissions to a role.
     *
     * @param Request $request
     * @param string $roleName
     * @return JsonResponse
     */
    public function assignPermissionToRole(Request $request, $roleName): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,name',
            ]);

            $role = Role::where('name', $roleName)->firstOrFail();

            $role->syncPermissions($request->permissions);

            return $this->sendSuccess([
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'message' => 'Cấp quyền cho vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cấp quyền cho vai trò:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi cấp quyền cho vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Soft delete a user from the system.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function destroy(Request $request, $userId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $user = User::findOrFail($userId);

            if ($user->protected) {
                return $this->sendError('Không thể xóa người dùng được bảo vệ.', Response::HTTP_FORBIDDEN);
            }

            $user->delete();

            return $this->sendSuccess([
                'user_id' => $user->id,
                'message' => 'Xóa người dùng thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa người dùng:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi xóa người dùng.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listRoles(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $roles = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? null,
                    'description' => $role->description ?? null,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'display_name' => $permission->display_name ?? null,
                            'description' => $permission->description ?? null,
                        ];
                    })
                ];
            });

            return $this->sendSuccess([
                'roles' => $roles,
                'message' => 'Lấy danh sách vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách vai trò:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listPermissions(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $permissions = Permission::all()->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? null,
                    'description' => $permission->description ?? null,
                ];
            });

            return $this->sendSuccess([
                'permissions' => $permissions,
                'message' => 'Lấy danh sách quyền thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách quyền:', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách quyền.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách tất cả vai trò (có thể lọc theo protected nếu cần)
     * GET api/roles/all-by-protected
     */
    public function listAllRolesByProtected(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $roles = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? null,
                    'description' => $role->description ?? null,
                    'protected' => $role->protected ?? null,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'name' => $permission->name,
                            'display_name' => $permission->display_name ?? null,
                            'description' => $permission->description ?? null,
                        ];
                    })
                ];
            });

            return $this->sendSuccess([
                'roles' => $roles,
                'message' => 'Lấy danh sách tất cả vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách tất cả vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách tất cả vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tạo mới vai trò
     * POST api/roles
     */
    public function createRole(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'name' => 'required|string|unique:roles,name',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:roles,id',
            ]);

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
            ]);

            return $this->sendSuccess([
                'role' => $role,
                'message' => 'Tạo vai trò thành công.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi tạo vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật vai trò
     * PUT api/roles/{roleId}
     */
    public function updateRole(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'name' => 'required|string|unique:roles,name,' . $roleId,
                'description' => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:roles,id',
            ]);

            $role = Role::findOrFail($roleId);
            $role->name = $request->name;
            $role->description = $request->description;
            $role->parent_id = $request->parent_id;
            $role->save();

            return $this->sendSuccess([
                'role' => $role,
                'message' => 'Cập nhật vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi cập nhật vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Xóa vai trò
     * DELETE api/roles/{roleId}
     */
    public function deleteRole(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $role = Role::findOrFail($roleId);
            if (isset($role->protected) && $role->protected) {
                return $this->sendError('Không thể xóa vai trò được bảo vệ.', Response::HTTP_FORBIDDEN);
            }
            $role->delete();

            return $this->sendSuccess([
                'role_id' => $roleId,
                'message' => 'Xóa vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi xóa vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách user thuộc vai trò
     * GET api/roles/{roleId}/users
     */
    public function getUsersOfRole(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $role = Role::findOrFail($roleId);
            $users = $role->users()->get();

            return $this->sendSuccess([
                'users' => $users,
                'message' => 'Lấy danh sách user của vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy user của vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi lấy user của vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách user không thuộc vai trò
     * GET api/roles/{roleId}/excluded-users
     */
    public function getUsersWithoutRole(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $query = User::whereDoesntHave('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });

            // Có thể thêm filter từ $request nếu cần
            $users = $query->get();

            return $this->sendSuccess([
                'users' => $users,
                'message' => 'Lấy danh sách user không thuộc vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy user không thuộc vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi lấy user không thuộc vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Thêm user vào vai trò (update-role-user)
     * PUT api/roles/{roleId}/update-role-user
     */
    public function updateRoleUser(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $role = Role::findOrFail($roleId);
            $role->users()->sync($request->user_ids);

            return $this->sendSuccess([
                'role_id' => $roleId,
                'user_ids' => $request->user_ids,
                'message' => 'Cập nhật user cho vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật user cho vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi cập nhật user cho vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Xóa user khỏi vai trò (remove-role-user)
     * DELETE api/roles/remove-role-user
     */
    public function removeRoleUser(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'role_id' => 'required|integer|exists:roles,id',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $role = Role::findOrFail($request->role_id);
            $role->users()->detach($request->user_id);

            return $this->sendSuccess([
                'role_id' => $request->role_id,
                'user_id' => $request->user_id,
                'message' => 'Xóa user khỏi vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xóa user khỏi vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi xóa user khỏi vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cập nhật quyền cho vai trò (update-role-permission)
     * POST api/roles/update-role-permission
     */
    public function updateRolePermission(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'role_id' => 'required|integer|exists:roles,id',
                'permission_id' => 'required|array',
                'permission_id.*' => 'integer|exists:permissions,id',
            ]);

            $role = Role::findOrFail($request->role_id);
            $role->syncPermissions($request->permission_id);

            return $this->sendSuccess([
                'role_id' => $role->id,
                'message' => 'Cập nhật quyền cho vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật quyền cho vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi cập nhật quyền cho vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy danh sách quyền của vai trò
     * GET api/permissions/{roleId}
     */
    public function getPermissionsOfRole(Request $request, $roleId): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $role = Role::findOrFail($roleId);
            $permissions = $role->permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? null,
                    'description' => $permission->description ?? null,
                ];
            });

            return $this->sendSuccess([
                'permissions' => $permissions,
                'message' => 'Lấy danh sách quyền của vai trò thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách quyền của vai trò:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi lấy danh sách quyền của vai trò.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lấy tất cả các quyền có trên hệ thống
     * GET api/permissions/all
     */
    public function getAllPermissions(Request $request): JsonResponse
    {
        try {
            $user = $this->authenticateUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized: Vui lòng đăng nhập để thực hiện hành động này.', Response::HTTP_UNAUTHORIZED);
            }

            $permissions = Permission::all()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? null,
                    'description' => $permission->description ?? null,
                ];
            });

            return $this->sendSuccess([
                'permissions' => $permissions,
                'message' => 'Lấy tất cả quyền thành công.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy tất cả quyền:', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendError('Đã xảy ra lỗi khi lấy tất cả quyền.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
