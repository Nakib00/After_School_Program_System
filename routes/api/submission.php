<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubmissionController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('submission')->group(function () {

        // Student Only (Submit)
        Route::middleware('role:student,super_admin,center_admin,teacher')->post('/', [SubmissionController::class, 'store']);

        // Teacher, Super Admin (Grade)
        Route::middleware('role:super_admin,teacher')->patch('/{id}/grade', [SubmissionController::class, 'grade']);

        // Teacher, Center Admin, Super Admin (List pending)
        Route::middleware('role:super_admin,center_admin,teacher,student')->group(function () {
            Route::get('/pending', [SubmissionController::class, 'pendingSubmissions']);
            Route::get('/assignment/{assignmentId}', [SubmissionController::class, 'getByAssignmentId']);
            Route::get('/{id}', [SubmissionController::class, 'show']);
        });
    });
});
