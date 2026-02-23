<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WorksheetController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('worksheet')->group(function () {

        // Teacher, Center Admin, Super Admin
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/', [WorksheetController::class, 'index']);
            Route::post('/', [WorksheetController::class, 'store']);
            Route::put('/{id}', [WorksheetController::class, 'update']);
        });

        // Center Admin, Super Admin
        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::delete('/{id}', [WorksheetController::class, 'destroy']);
        });

        // Public (All Roles or specific logic)
        Route::get('/{id}', [WorksheetController::class, 'show']);
        Route::get('/{id}/download', [WorksheetController::class, 'download']);
    });
});
