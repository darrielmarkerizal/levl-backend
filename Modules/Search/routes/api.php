<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;

Route::prefix('v1')->group(function () {
    // Public search endpoint (no auth required for searching)
    Route::get('search/courses', [SearchController::class, 'search'])->name('search.courses');
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');

    // Protected search history endpoints (require authentication)
    Route::middleware(['auth:api'])->group(function () {
        Route::get('search/history', [SearchController::class, 'getSearchHistory'])->name('search.history');
        Route::delete('search/history', [SearchController::class, 'clearSearchHistory'])->name('search.history.clear');
    });
});
