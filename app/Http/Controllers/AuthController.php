<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

use App\Models\User;
use App\Constants\Permissions;
use App\Dtos\UserDto;
use App\Services\UserService;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;

class AuthController extends Controller
{
    public function __construct(private readonly UserService $userService) {
    }

    public function register(RegisterUserRequest $request):JsonResponse{
        $userDto = UserDto::fromApiFormRequest($request);
        $user = $this->userService->createUser($userDto);
        return $this->sendSuccess(['user'=>$user],'Tạo người dùng mới thành công!');
    }

    public function login (LoginRequest $request): JsonResponse{
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            Log::warning('Login failed - User not found', ['email' => $credentials['email']]);
            return $this->sendError( 'Email hoặc mật khẩu không chính xác', 401);
        }

        // Tìm user theo email
        $user = User::query()->where('email', $credentials['email'])->first();
        $user->tokens()->delete();

        // Kiểm tra trạng thái tài khoản
        if ($user->status === 0) {
            Log::warning('Login failed - Account is inactive', ['email' => $credentials['email']]);
            return $this->sendError( 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.', 403);
        }

        // Đăng nhập thành công - Tạo token
         // Define token expiration times
         $accessTokenExpiresAt = now()->addDays(1);
         $refreshTokenExpiresAt = now()->addDays(7);

         // Create access and refresh tokens
         $accessToken = $user->createToken('access_token', Permissions::all(), $accessTokenExpiresAt)->plainTextToken;
         $refreshToken = $user->createToken('refresh_token', [Permissions::REFRESH['token']], $refreshTokenExpiresAt)->plainTextToken;

        Log::info('User logged in successfully', ['email' => $credentials['email'], 'user_id' => $user->id]);

        return $this->sendSuccess([
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
        ], 'Đăng nhập thành công');
    }

    public function refreshToken(Request $request):JsonResponse
    {
        $currentRefreshToken = $request->bearerToken();
        $refreshToken = PersonalAccessToken::findToken($currentRefreshToken);

        if (!$refreshToken || !$refreshToken->can('refresh') || $refreshToken->expires_at->isPast()) {
            return $this->sendError('Token không hợp lệ', 401);
        }

        $user = $refreshToken->tokenable;
        $refreshToken->delete();

        $accessTokenExpiresAt = now()->addDays(1);
        $refreshTokenExpiresAt = now()->addDays(7);

        $newAccessToken = $user->createToken('access_token', Permissions::all(), $accessTokenExpiresAt)->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', Permissions::REFRESH['token'], $refreshTokenExpiresAt)->plainTextToken;

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Revoke toàn bộ token hiện tại (nếu dùng Passport hoặc Sanctum)
            $user->currentAccessToken()->delete();

            return $this->sendSuccess($user, "Đăng xuất thành công");
        }

        return $this->sendError('Không có người dùng xác thực', 401);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->sendError(
                message: 'Người dùng không xác thực',
                statusCode: 401,
            );
        }

        return $this->sendSuccess(
            data: [
                'user' => $user,
            ],
            message: 'Truy xuất người dùng xác thực thành công'
        );
    }

}
