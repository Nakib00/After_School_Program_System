<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubmissionController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('submission')->group(function () {

        // Student Only (Submit)
        Route::middleware('role:student,super_admin,center_admin,teacher')->post('/', [SubmissionController::class, 'store']);

        // Teacher, Super Admin (Grade)
        Route::middleware('role:super_admin,teacher')->group(function () {
            Route::patch('/{id}/grade', [SubmissionController::class, 'grade']);
            Route::put('/{id}/update-grade', [SubmissionController::class, 'updateGrade']);
        });

        // Teacher, Center Admin, Super Admin
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/', [SubmissionController::class, 'index']);
            Route::get('/pending', [SubmissionController::class, 'pendingSubmissions']);
        });

        // Authorized users (Student can view their own, others can view all)
        Route::middleware('role:super_admin,center_admin,teacher,student')->group(function () {
            Route::get('/assignment/{assignmentId}', [SubmissionController::class, 'getByAssignmentId']);
            Route::get('/{id}', [SubmissionController::class, 'show']);
            Route::get('/{id}/download', [SubmissionController::class, 'download']);
        });
    });
});
