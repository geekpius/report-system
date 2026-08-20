<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\RegisteredClientController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/sign-up', [RegisteredClientController::class, 'store'])
    ->middleware('throttle:api-signup')
    ->name('api.auth.sign-up');

Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:api-login')
    ->name('api.auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthenticatedSessionController::class, 'show'])
        ->name('api.auth.me');

    Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('api.auth.logout');
});
