<?php

namespace App\Http\Controllers;

use App\Constants\Permissions;
use App\Dtos\UserDto;
use App\Events\InfoAuthUpdated;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProfileRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Profiler\Profile;

class AuthController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * Đăng ký tài khoản mới
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $userDto = UserDto::fromApiFormRequest($request);
        $user = $this->userService->createUser($userDto);

        return $this->sendSuccess(
            ['user' => $user],
            'Tạo người dùng mới thành công!'
        );
    }

    /**
     * Đăng nhập hệ thống
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            Log::warning('Login failed - Invalid credentials', ['email' => $credentials['email']]);
            return $this->sendError('Email hoặc mật khẩu không chính xác', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Xóa token cũ (nếu có)
        $user->tokens()->delete();

        if ($user->status === 0) {
            Log::warning('Login failed - Account inactive', ['email' => $user->email]);
            return $this->sendError('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.', 403);
        }

        [$accessToken, $refreshToken] = $this->generateTokensForUser($user);

        Log::info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

        // Trả về token trong response (hoặc set vào cookie nếu cần)
        return $this->sendSuccess([
            'user' => $user,
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
            ->cookie('access_token',-1)
            ->cookie('refresh_token',-1);
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
            return $this->sendError('Người dùng chưa đăng nhập', 401);
        }

        return $this->sendSuccess(['user' => $user], 'Truy xuất thông tin người dùng thành công');
    }

    public function update(ProfileRequest $request)
    {
        $userDto = UserDto::fromApiFormRequest($request);
        $user = $this->userService->updateUser($userDto);

        return $this->sendSuccess(['user' => $user],'Cập nhật thông tin người dùng thành công');
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
