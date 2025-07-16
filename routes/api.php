<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventAttachmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public Routes
Route::group(['as' => 'api.'], function () {
    // Authentication Routes
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('callback', [AuthController::class, 'login'])->name('callback');
    });

    // Department Routes
    Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
    // Event Routes
    Route::apiResource('events', EventController::class)->only(['index', 'show']);
    // Event Attachments Routes
    Route::apiResource('attachments', EventAttachmentController::class)->only(['index', 'show']);
    // Location Routes
    Route::apiResource('locations', LocationController::class)->only(['index', 'show']);
});

// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // User Routes
    Route::get('/user', fn(Request $request) => $request->user())
        ->name('user')
        ->middleware('can:view_profile');

    // Authentication Routes
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::get('me', [AuthController::class, 'me'])
            ->name('me');
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('logout');
        Route::post('refresh', [AuthController::class, 'refreshToken'])
            ->name('refresh')
            ->middleware('can:refresh_token');
        Route::patch('profile', [AuthController::class, 'update'])
            ->name('profile')
            ->middleware('can:update_profile');
    });

    // Department Routes
    Route::post('departments', [DepartmentController::class, 'store'])
        ->middleware('can:create_departments');
    Route::put('departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('can:edit_departments');
    Route::patch('departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('can:edit_departments');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('can:delete_departments');

    // Event Routes
    Route::post('events', [EventController::class, 'store'])
        ->middleware('can:create_events');
    Route::put('events/{event}', [EventController::class, 'update'])
        ->middleware(['can:edit_own_events', 'can:edit_all_events']);
    Route::patch('events/{event}', [EventController::class, 'update'])
        ->middleware(['can:edit_own_events', 'can:edit_all_events']);
    Route::delete('events/{event}', [EventController::class, 'destroy'])
        ->middleware(['can:delete_own_events', 'can:delete_all_events']);
    Route::post('events/{id}/approve', [EventController::class, 'approve'])->middleware('can:approve_events');

    // Event Attachments Routes
    Route::post('attachments', [EventAttachmentController::class, 'store'])
        ->middleware('can:create_attachments');
    Route::put('attachments/{attachment}', [EventAttachmentController::class, 'update'])
        ->middleware('can:edit_attachments');
    Route::patch('attachments/{attachment}', [EventAttachmentController::class, 'update'])
        ->middleware('can:edit_attachments');
    Route::delete('attachments/{attachment}', [EventAttachmentController::class, 'destroy'])
        ->middleware('can:delete_attachments');

    // Location Routes
    Route::post('locations', [LocationController::class, 'store'])
        ->middleware('can:create_locations');
    Route::put('locations/{location}', [LocationController::class, 'update'])
        ->middleware('can:edit_locations');
    Route::patch('locations/{location}', [LocationController::class, 'update'])
        ->middleware('can:edit_locations');
    Route::delete('locations/{location}', [LocationController::class, 'destroy'])
        ->middleware('can:delete_locations');

    // User Management Routes
    Route::prefix('users')->as('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index')
            ->middleware('can:view_users');
        Route::post('{userId}/assign-role', [UserController::class, 'assignRole'])
            ->name('assign-role')
            ->middleware('can:assign_roles');
        Route::delete('{userId}', [UserController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:delete_users');
    });

    // Role Management Routes
    Route::prefix('roles')->as('roles.')->group(function () {
        // ...existing code...
        // API lấy tất cả quyền hệ thống
        Route::get('/permissions/all', [UserController::class, 'getAllPermissions'])
            ->name('permissions.all')
            ->middleware('can:view_permissions');
        Route::post('{roleName}/assign-permission', [UserController::class, 'assignPermissionToRole'])
            ->name('assign-permission')
            ->middleware('can:assign_permissions');
        // Danh sách vai trò
        Route::get('/', [UserController::class, 'listRoles'])
            ->name('list')
            ->middleware('can:view_roles');
        // Danh sách tất cả vai trò (all-by-protected)
        Route::get('all-by-protected', [UserController::class, 'listAllRolesByProtected'])
            ->name('all-by-protected')
            ->middleware('can:view_roles');
        // Tạo mới vai trò
        Route::post('/', [UserController::class, 'createRole'])
            ->name('create')
            ->middleware('can:create_roles');
        // Cập nhật vai trò
        Route::put('{roleId}', [UserController::class, 'updateRole'])
            ->name('update')
            ->middleware('can:edit_roles');
        // Xóa vai trò
        Route::delete('{roleId}', [UserController::class, 'deleteRole'])
            ->name('delete')
            ->middleware('can:delete_roles');
        // Lấy user thuộc vai trò
        Route::get('{roleId}/users', [UserController::class, 'getUsersOfRole'])
            ->name('users')
            ->middleware('can:view_users');
        // Lấy user không thuộc vai trò
        Route::get('{roleId}/excluded-users', [UserController::class, 'getUsersWithoutRole'])
            ->name('excluded-users')
            ->middleware('can:view_users');
        // Thêm user vào vai trò
        Route::put('{roleId}/update-role-user', [UserController::class, 'updateRoleUser'])
            ->name('update-role-user')
            ->middleware('can:edit_roles');
        // Xóa user khỏi vai trò
        Route::delete('remove-role-user', [UserController::class, 'removeRoleUser'])
            ->name('remove-role-user')
            ->middleware('can:edit_roles');
        // Cập nhật quyền cho vai trò
        Route::post('update-role-permission', [UserController::class, 'updateRolePermission'])
            ->name('update-role-permission')
            ->middleware('can:assign_permissions');
        // Lấy danh sách quyền của vai trò
        Route::get('/permissions/{roleId}', [UserController::class, 'getPermissionsOfRole'])
            ->name('permissions-of-role')
            ->middleware('can:view_permissions');
    });
});
