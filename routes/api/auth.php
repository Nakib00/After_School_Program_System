<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SuperAdminController;


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Role-guarded examples
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/super-admin/dashboard', [SuperAdminController::class, 'dashboard']);
        Route::get('/center-admins', [AuthController::class, 'indexCenterAdmins']);
        Route::patch('/users/{id}/toggle-status', [SuperAdminController::class, 'toggleUserStatus']);
        Route::delete('/center-admins/{id}', [SuperAdminController::class, 'deleteCenterAdmin']);
    });


    Route::middleware('role:center_admin,super_admin')->group(function () {
        Route::get('/parents', [AuthController::class, 'indexParents']);
    });
});
