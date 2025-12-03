<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationPreferenceController;
use Modules\Notifications\Http\Controllers\NotificationsController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    Route::apiResource('notifications', NotificationsController::class)->names('notifications');

    // Notification Preferences
    Route::get('notification-preferences', [NotificationPreferenceController::class, 'index'])->name('notification-preferences.index');
    Route::put('notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
    Route::post('notification-preferences/reset', [NotificationPreferenceController::class, 'reset'])->name('notification-preferences.reset');
});
