<?php

namespace App\Services;

use App\Constants\Permissions;
use App\Dtos\UserDto;
use App\Events\InfoAuthUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Đăng nhập hệ thống
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            Log::warning('Login failed - Invalid credentials', ['email' => $credentials['email']]);
            throw new \Exception('Email hoặc mật khẩu không chính xác');
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->status === 0) {
            Log::warning('Login failed - Account inactive', ['email' => $user->email]);
            throw new \Exception('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        // Xóa token cũ (nếu có)
        $user->tokens()->delete();

        return $this->generateTokensForUser($user);
    }

    /**
     * Làm mới token
     */
    public function refreshToken(string $currentRefreshToken): array
    {
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || !$refreshToken->can('refresh') || $refreshToken->expires_at->isPast()) {
            Log::warning('Refresh token failed - Invalid or expired token');
            throw new \Exception('Token không hợp lệ hoặc đã hết hạn');
        }

        /** @var User $user */
        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        return $this->generateTokensForUser($user);
    }

    /**
     * Đăng xuất
     */
    public function logout(User $user): void
    {
        if ($user) {
            $user->currentAccessToken()?->delete();
            Log::info('User logged out', ['user_id' => $user->id]);
        }
    }

    /**
     * Lấy thông tin người dùng đang đăng nhập
     */
    public function getCurrentUser(): User
    {
        return Auth::user();
    }

    /**
     * Cập nhật thông tin người dùng
     */
    public function update(UserDto $userDto): User
    {
        $user = User::findOrFail($userDto->getId());
        $user->update([
            'first_name' => $userDto->getFirstName(),
            'last_name'=> $userDto->getLastName(),
            'phone' => $userDto->getPhone(),
            'department_id' => $userDto->getDepartmentId(),
        ]);

        event(new InfoAuthUpdated($user));

        return $user;
    }

    /**
     * Tạo access_token & refresh_token cho user
     */
    private function generateTokensForUser(User $user): array
    {
        $accessTokenExpiresAt = now()->addDays(1);
        $refreshTokenExpiresAt = now()->addDays(7);

        $accessToken = $user->createToken('access_token', Permissions::all(), $accessTokenExpiresAt)->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', [Permissions::REFRESH['token']], $refreshTokenExpiresAt)->plainTextToken;

        return [$accessToken, $refreshToken];
    }
}
