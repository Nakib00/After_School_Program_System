<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;


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
        Route::get('/super-admin/dashboard', function () {
            return response()->json(['message' => 'Welcome Super Admin']);
        });
        Route::get('/center-admins', [AuthController::class, 'indexCenterAdmins']);
    });

    Route::middleware('role:center_admin,super_admin')->group(function () {
        Route::get('/parents', [AuthController::class, 'indexParents']);
    });
});
