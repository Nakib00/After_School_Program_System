<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AssignmentController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('assignment')->group(function () {

        // Teacher, Center Admin, Super Admin
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/', [AssignmentController::class, 'index']);
            Route::get('/{id}', [AssignmentController::class, 'show']);
        });

        // Teacher, Super Admin
        Route::middleware('role:super_admin,teacher')->group(function () {
            Route::post('/', [AssignmentController::class, 'store']); // Bulk assign
            Route::put('/{id}', [AssignmentController::class, 'update']);
        });

        // Teacher, Center Admin, Super Admin - Cancellation
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::delete('/{id}', [AssignmentController::class, 'destroy']);
        });
    });
});
