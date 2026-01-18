<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
  return view("welcome");
});

// Load test routes
// Load test routes
require __DIR__ . "/test-browser.php";

Route::get('/dev/octane-check', [App\Http\Controllers\DevController::class, 'checkOctane']);
Route::get('/dev/benchmark', [App\Http\Controllers\DevController::class, 'benchmarkView']);

// Benchmark API - Remove session middleware for performance
Route::get('/dev/benchmark-api', [App\Http\Controllers\DevController::class, 'benchmarkApi'])
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
    ]);



