<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TeacherController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('teacher')->group(function () {

        // Specific static routes first (accessible by required roles)
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/dashboard', [TeacherController::class, 'dashboard']);
        });

        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::post('/assign-students', [TeacherController::class, 'assignStudent']);
            Route::post('/unassign-students', [TeacherController::class, 'unassignStudent']);
        });

        // Resource-like routes and wildcards
        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::get('/', [TeacherController::class, 'index']);
            Route::post('/', [TeacherController::class, 'store']);
            Route::get('/{id}', [TeacherController::class, 'show']);
            Route::put('/{id}', [TeacherController::class, 'update']);
            Route::delete('/{id}', [TeacherController::class, 'destroy']);
        });

        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/{id}/students', [TeacherController::class, 'assignedStudents']);
        });
    });
});
