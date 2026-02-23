<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CenterController;


Route::middleware('auth:api')->group(function () {
    Route::prefix('center')->group(function () {
        // Super Admin Only
        Route::middleware('role:super_admin')->group(function () {
            Route::post('/', [CenterController::class, 'store']);
            Route::get('/', [CenterController::class, 'index']);
            Route::put('/{id}', [CenterController::class, 'update']);
            Route::delete('/{id}', [CenterController::class, 'destroy']);
        });

        // Super Admin and Center Admin
        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::get('/stats/{id}', [CenterController::class, 'stats']);
            Route::get('/{id}', [CenterController::class, 'show']);
        });
    });
});
