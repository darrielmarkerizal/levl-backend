<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\ContentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('contents', ContentController::class)->names('content');
});
