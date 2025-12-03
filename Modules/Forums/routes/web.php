<?php

use Illuminate\Support\Facades\Route;
use Modules\Forums\Http\Controllers\ForumsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('forums', ForumsController::class)->names('forums');
});
