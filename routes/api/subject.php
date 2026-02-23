<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubjectController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('subject')->group(function () {

        // Public (All Roles) - List active subjects
        Route::get('/', [SubjectController::class, 'index']);

        // Super Admin Only
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/all', [SubjectController::class, 'listAll']);
            Route::post('/', [SubjectController::class, 'store']);
            Route::get('/{id}', [SubjectController::class, 'show']);
            Route::put('/{id}', [SubjectController::class, 'update']);
            Route::patch('/{id}/toggle-status', [SubjectController::class, 'toggleStatus']);
        });
    });
});
