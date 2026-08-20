<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\Profile\UpdateSchoolController;
use App\Http\Controllers\Api\Auth\RegisteredClientController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/sign-up', [RegisteredClientController::class, 'store'])
    ->middleware('throttle:api-signup')
    ->name('api.auth.sign-up');

Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:api-login')
    ->name('api.auth.login');

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
    });
});
