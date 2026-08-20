<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\Profile\UpdateSchoolController;
use App\Http\Controllers\Api\Auth\Profile\UpdateStudentController;
use App\Http\Controllers\Api\Auth\Profile\UpdateTeacherController;
use App\Http\Controllers\Api\Auth\RegisteredClientController;
use App\Http\Controllers\Api\SchoolClass\SchoolClassController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/sign-up', [RegisteredClientController::class, 'store'])
    ->middleware('throttle:api-signup')
    ->name('api.auth.sign-up');

Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:api-login')
    ->name('api.auth.login');

Route::post('/auth/forgot-password', [PasswordResetController::class, 'store'])
    ->middleware('throttle:api-forgot-password')
    ->name('api.auth.forgot-password');

Route::post('/auth/reset-password', [PasswordResetController::class, 'update'])
    ->middleware('throttle:api-forgot-password')
    ->name('api.auth.reset-password');

Route::middleware('auth:sanctum')->group(function () {
    // auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthenticatedSessionController::class, 'show'])
            ->name('api.me');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('api.logout');

        // profile routes
        Route::put('/profile/schools/{school}', [UpdateSchoolController::class, 'update'])
            ->middleware('abilities:permit:owner')
            ->name('api.profile.schools.update');

        Route::put('/profile/teachers/{teacher}', [UpdateTeacherController::class, 'update'])
            ->middleware('abilities:permit:teacher')
            ->name('api.profile.teachers.update');

        Route::put('/profile/students/{student}', [UpdateStudentController::class, 'update'])
            ->middleware('abilities:permit:student')
            ->name('api.profile.students.update');
    });

    Route::prefix('schools/{school}/classes')->middleware('abilities:permit:owner')->group(function () {
        Route::get('/', [SchoolClassController::class, 'index'])
            ->name('api.schools.classes.index');

        Route::post('/', [SchoolClassController::class, 'store'])
            ->name('api.schools.classes.store');
    });
});
