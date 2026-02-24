<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('attendance')->group(function () {

        // Super Admin, Center Admin, Teacher
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::post('/bulk', [AttendanceController::class, 'store']);
            Route::put('/{id}', [AttendanceController::class, 'update']);
            Route::get('/today', [AttendanceController::class, 'todayAttendance']);
        });

        // Super Admin, Center Admin, Teacher, Parent (History)
        Route::middleware('role:super_admin,center_admin,teacher,parent')->group(function () {
            Route::get('/', [AttendanceController::class, 'index']);
        });

        // Super Admin, Center Admin (Report)
        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::get('/summary', [AttendanceController::class, 'monthlySummary']);
        });
    });
});
