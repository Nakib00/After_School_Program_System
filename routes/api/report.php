<?php

use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::get('/dashboard/kpis', [ReportController::class, 'dashboardKpis']);
    Route::get('/reports/center-performance', [ReportController::class, 'centerPerformance']);
    Route::get('/reports/teacher-performance', [ReportController::class, 'teacherPerformance']);
    Route::get('/reports/student-detailed/{id}', [ReportController::class, 'studentDetailedReport']);
    Route::get('/reports/fee-collection', [ReportController::class, 'feeCollectionReport']);
    Route::get('/reports/attendance', [ReportController::class, 'attendanceReport']);
    Route::get('/reports/level-progression', [ReportController::class, 'levelProgressionReport']);
});
