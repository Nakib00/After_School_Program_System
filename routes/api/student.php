<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;

Route::middleware('auth:api')->group(function () {
    Route::prefix('student')->group(function () {

        // Super Admin and Center Admin
        Route::middleware('role:super_admin,center_admin')->group(function () {
            Route::post('/', [StudentController::class, 'store']);
            Route::delete('/{id}', [StudentController::class, 'destroy']);
        });

        // Super Admin, Center Admin, Teacher
        Route::middleware('role:super_admin,center_admin,teacher')->group(function () {
            Route::get('/', [StudentController::class, 'index']);
            Route::put('/{id}', [StudentController::class, 'update']);
        });

        // Super Admin, Center Admin, Teacher, Parent
        Route::middleware('role:super_admin,center_admin,teacher,parent')->group(function () {
            Route::get('/{id}', [StudentController::class, 'show']);
            Route::get('/{id}/assignments', [StudentController::class, 'assignments']);
            Route::get('/{id}/attendance', [StudentController::class, 'attendance']);
        });

        // Super Admin, Center Admin, Parent
        Route::middleware('role:super_admin,center_admin,parent')->group(function () {
            Route::get('/{id}/fees', [StudentController::class, 'fees']);
        });

        // Accessible by all authenticated users
        Route::get('/{id}/progress', [StudentController::class, 'progress']);
    });
});
