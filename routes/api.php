<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Đây là nơi đăng ký các API route của ứng dụng. Các route này được load
| bởi RouteServiceProvider và được gán middleware "api". Hãy tận hưởng việc
| xây dựng API của bạn!
|
*/

// Route lấy thông tin người dùng đã xác thực
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Nhóm các route liên quan đến xác thực
Route::prefix('auth')->as('auth.')->group(function () {

    // Các route công khai
    Route::middleware('guest')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('refresh', [AuthController::class, 'refreshToken'])->name('refresh');
    });

    // Các route cần bảo vệ bằng middleware auth:sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    // Route xác minh email (link trong email sẽ chứa {id} và {hash})
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->name('verification.verify')
        ->middleware('signed');

    // Route gửi lại email xác minh
    Route::post('/email/resend', [VerificationController::class, 'resend'])
        ->middleware(['auth:api']) // Chỉ người dùng đã đăng nhập mới gửi lại
        ->name('verification.resend');
});

