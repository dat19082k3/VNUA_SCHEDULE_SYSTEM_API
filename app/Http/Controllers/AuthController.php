<?php

namespace App\Http\Controllers;

use App\Constants\Permissions;
use App\Dtos\UserDto;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileRequest;
use App\Models\User;
use App\Services\UserService;
use App\Services\AuthService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    protected UserService $userService;
    protected AuthService $authService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    /**
     * Đăng nhập hệ thống
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->authService->login($validated);
        [$accessToken, $refreshToken] = $this->generateTokensForUser($user);

        Log::info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

        return $this->sendSuccess([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
        ], 'Đăng nhập thành công');
    }

    /**
     * Làm mới token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $currentRefreshToken = $request->bearerToken();
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || !$refreshToken->can('refresh') || $refreshToken->expires_at->isPast()) {
            Log::warning('Refresh token failed - Invalid or expired token');
            return $this->sendError('Token không hợp lệ hoặc đã hết hạn', 401);
        }

        /** @var User $user */
        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        [$newAccessToken, $newRefreshToken] = $this->generateTokensForUser($user);

        return $this->sendSuccess([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
        ], 'Token mới đã được cấp')
            ->cookie('access_token', $newAccessToken, 60 * 24, '/', null, false, true)
            ->cookie('refresh_token', $newRefreshToken, 60 * 24 * 7, '/', null, false, true);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()?->delete();
            Log::info('User logged out', ['user_id' => $user->id]);
            return $this->sendSuccess(null, 'Đăng xuất thành công')
                ->cookie('access_token', -1)
                ->cookie('refresh_token', -1);
        }

        return $this->sendError('Không có người dùng nào đang đăng nhập', 401);
    }

    /**
     * Lấy thông tin người dùng đang đăng nhập
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->sendError('Người dùng chưa đăng nhập', Response::HTTP_UNAUTHORIZED);
        }

        // Lấy vai trò và quyền của người dùng
        $roles = $user->getRoleNames(); // Lấy danh sách tên vai trò
        $permissions = $user->getAllPermissions()->pluck('name'); // Lấy danh sách tên quyền

        // Tạo đối tượng user với roles và permissions lồng bên trong
        $userData = $user->toArray();
        $userData['roles'] = $roles;
        $userData['permissions'] = $permissions;

        return $this->sendSuccess([
            'user' => $userData,
        ], 'Truy xuất thông tin người dùng thành công');
    }

    public function update(ProfileRequest $request)
    {
        $userDto = UserDto::fromApiFormRequest($request);
        $user = $this->userService->updateUser($userDto);

        return $this->sendSuccess(['user' => $user], 'Cập nhật thông tin người dùng thành công');
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
