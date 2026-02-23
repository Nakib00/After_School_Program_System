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

    // Role-guarded examples
    Route::get('/super-admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Super Admin']);
    })->middleware('role:super_admin');

    Route::get('/center-admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Center Admin']);
    })->middleware('role:center_admin');

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
