<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;


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
        Route::get('/center-admin/dashboard', function () {
            return response()->json(['message' => 'Welcome Center Admin']);
        });
        Route::get('/parents', [AuthController::class, 'indexParents']);
    });


    Route::get('/teacher/dashboard', function () {
        return response()->json(['message' => 'Welcome Teacher']);
    })->middleware('role:teacher');

    Route::get('/student/dashboard', function () {
        return response()->json(['message' => 'Welcome Student']);
    })->middleware('role:student');

    Route::get('/parent/dashboard', function () {
        return response()->json(['message' => 'Welcome Parent']);
    })->middleware('role:parent');
});
