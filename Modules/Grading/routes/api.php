<?php

use Illuminate\Support\Facades\Route;
use Modules\Grading\Http\Controllers\AppealController;
use Modules\Grading\Http\Controllers\GradingController;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {

    Route::get('appeals/pending', [AppealController::class, 'pending'])->name('appeals.pending');
    Route::get('appeals/{appeal}', [AppealController::class, 'show'])->name('appeals.show');
    Route::post('appeals/{appeal}/approve', [AppealController::class, 'approve'])->name('appeals.approve');
    Route::post('appeals/{appeal}/deny', [AppealController::class, 'deny'])->name('appeals.deny');
    Route::post('submissions/{submission}/appeals', [AppealController::class, 'submit'])->name('appeals.submit');

    Route::middleware(['role:Superadmin|Admin|Instructor'])->group(function () {
        Route::get('grading/queue', [GradingController::class, 'queue'])->name('grading.queue');
        Route::post('grading/bulk-release', [GradingController::class, 'bulkReleaseGrades'])->name('grading.bulk-release');
        Route::post('grading/bulk-feedback', [GradingController::class, 'bulkApplyFeedback'])->name('grading.bulk-feedback');
    });

    Route::middleware(['role:Superadmin|Admin|Instructor'])->prefix('submissions/{submission}')->group(function () {
        Route::post('grade', [GradingController::class, 'manualGrade'])->name('grading.grade');
        Route::post('draft-grade', [GradingController::class, 'saveDraftGrade'])->name('grading.save-draft');
        Route::get('draft-grade', [GradingController::class, 'getDraftGrade'])->name('grading.get-draft');
        Route::post('override-grade', [GradingController::class, 'overrideGrade'])->name('grading.override');
        Route::post('release-grade', [GradingController::class, 'releaseGrade'])->name('grading.release');
        Route::post('return-to-queue', [GradingController::class, 'returnToQueue'])->name('grading.return-to-queue');
        Route::get('grading-status', [GradingController::class, 'gradingStatus'])->name('grading.status');
    });
});
