<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthApiController;

Route::prefix('v1')->as('auth.')->group(function () {
    Route::post('/auth/register', [AuthApiController::class, 'register'])->name('register');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('login');

    Route::middleware(['auth:api'])->group(function () {
        Route::post('/auth/refresh', [AuthApiController::class, 'refresh'])->name('refresh');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('/profile', [AuthApiController::class, 'profile'])->name('profile');
    });
});
