<?php

use Illuminate\Support\Facades\Route;
use Modules\Benchmark\Http\Controllers\BenchmarkController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Benchmark routes - Publicly accessible for load testing
Route::prefix('benchmark')->group(function () {
    Route::get('baseline', [BenchmarkController::class, 'baseline']);
    Route::get('light', [BenchmarkController::class, 'light']);
    Route::get('heavy', [BenchmarkController::class, 'heavy']);
});
