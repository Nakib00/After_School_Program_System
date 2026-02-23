<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LevelController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('level')->group(function () {

        // Public (All Roles) - List levels
        Route::get('/', [LevelController::class, 'index']);

        // Super Admin Only
        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [LevelController::class, 'store']);
            Route::put('/{id}', [LevelController::class, 'update']);
            Route::delete('/{id}', [LevelController::class, 'destroy']);
        });
    });
});
