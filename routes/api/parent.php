<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;

Route::middleware(['auth:api', 'role:parent'])->group(function () {
    Route::prefix('parent')->group(function () {
        Route::get('/children-reports', [StudentController::class, 'childrenReports']);
        Route::get('/children-attendance', [StudentController::class, 'childrenAttendance']);
        Route::get('/children-assignments', [StudentController::class, 'childrenAssignments']);
        Route::get('/children-fees', [StudentController::class, 'childrenFees']);
    });
});
