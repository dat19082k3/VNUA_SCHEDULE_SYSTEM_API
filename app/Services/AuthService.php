<?php

namespace App\Services;

use App\Constants\Permissions;
use App\Dtos\UserDto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(protected SsoService $ssoService) {}

    /**
     * Đăng nhập qua hệ thống SSO
     */
    public function login(array $credentials): User
    {
        if (empty($credentials['code'])) {
            throw new \Exception('Thiếu mã xác thực từ hệ thống SSO.');
        }

        // Lấy token từ hệ thống SSO
        $token = $this->ssoService->getUserFromSsoCode($credentials['code']);

        if (!$token) {
            Log::warning(message: 'SSO login failed - Invalid or missing user data');
            throw new \Exception('Đăng nhập SSO thất bại. Không lấy được token từ hệ thống.');
        }

        //Lấy thông tin người dùng từ SSO
        $ssoUser = $this->ssoService->getUserData($token);
        Log::info('SSO user data retrieved', ['data' => $ssoUser]);
        if (!$ssoUser) {
            Log::warning(message: 'SSO login failed - Invalid or missing user data');
            throw new \Exception('Đăng nhập SSO thất bại. Không thể xác thực người dùng.');
        }

        // Kiểm tra xem đây có phải là người dùng đầu tiên hay không
        $isFirstUser = User::count() === 0;

        // Tìm hoặc tạo user trong hệ thống local
        $user = User::updateOrCreate(
            ['email' => $ssoUser['email']],
            [
                'sso_id' => $ssoUser['id'],
                'user_name' => $ssoUser['user_name'],
                'first_name' => $ssoUser['first_name'],
                'last_name' => $ssoUser['last_name'],
                'email' => $ssoUser['email'],
                'phone' => $ssoUser['phone'],
                'role_sso' => $ssoUser['role'],
                'status' => $ssoUser['status'],
                'code' => $ssoUser['code'],
                'department_id' => $ssoUser['department_id'],
                'faculty_id' => $ssoUser['faculty_id'],
                'protected' => $isFirstUser ? true : ($ssoUser['protected'] ?? false),
            ]
        );

        // Đảm bảo vai trò tồn tại trước khi gán
        $adminRole = Role::firstOrCreate(['name' => 'chief_of_office', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Gán vai trò
        if ($isFirstUser || $user->email === 'chanhvp@vnua.edu.vn') {
            $user->syncRoles([$adminRole]);
        } else {
            // Chỉ gán vai trò lecturer nếu người dùng chưa có vai trò
            $existingRoles = $user->roles()->where('guard_name', 'web')->pluck('name')->toArray();
            if (empty($existingRoles)) {
                $user->syncRoles([$staffRole]);
            }
        }


        Auth::login($user);
        $user->tokens()->delete();

        return $user;
    }

    public function refreshToken(string $currentRefreshToken): array
    {
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || !$refreshToken->can('refresh') || $refreshToken->expires_at->isPast()) {
            Log::warning('Refresh token failed - Invalid or expired token');
            throw new \Exception('Token không hợp lệ hoặc đã hết hạn');
        }

        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        return $this->generateTokensForUser($user);
    }

    public function logout(User $user): void
    {
        if ($user) {;
            Log::info('User logged out', ['user_id' => $user->id]);
        }
    }

    public function getCurrentUser(): User
    {
        return Auth::user();
    }

    public function update(UserDto $userDto): User
    {
        $user = User::findOrFail($userDto->getId());
        $user->update([
            'first_name' => $userDto->getFirstName(),
            'last_name' => $userDto->getLastName(),
            'phone' => $userDto->getPhone(),
            'department_id' => $userDto->getDepartmentId(),
        ]);

        return $user;
    }

    private function generateTokensForUser(User $user): array
    {
        $accessTokenExpiresAt = now()->addDays(1);
        $refreshTokenExpiresAt = now()->addDays(7);

        $accessToken = $user->createToken('access_token', Permissions::all(), $accessTokenExpiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', [Permissions::REFRESH['token']], $refreshTokenExpiresAt)->plainTextToken;

        return [$accessToken, $refreshToken];
    }
}
