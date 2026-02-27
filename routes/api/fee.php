<?php

use App\Http\Controllers\Api\FeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/fees', [FeeController::class, 'index']);
    Route::post('/fees/generate', [FeeController::class, 'generateMonthlyFees']);
    Route::get('/fees/report', [FeeController::class, 'report']);
    Route::get('/fees/unpaid-overdue', [FeeController::class, 'unpaidOverdue']);
    Route::post('/fees/mark-overdue', [FeeController::class, 'markAsOverdue']);

    Route::get('/fees/{id}', [FeeController::class, 'show']);
    Route::put('/fees/{id}', [FeeController::class, 'update']);
    Route::put('/fees/{id}/pay', [FeeController::class, 'markAsPaid']);
});
