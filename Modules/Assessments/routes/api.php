<?php

use Illuminate\Support\Facades\Route;
use Modules\Assessments\Http\Controllers\AssessmentRegistrationController;
use Modules\Assessments\Http\Controllers\AttemptController;
use Modules\Assessments\Http\Controllers\ExerciseController;
use Modules\Assessments\Http\Controllers\GradingController;
use Modules\Assessments\Http\Controllers\QuestionController;
use Modules\Assessments\Http\Controllers\QuestionOptionController;

Route::middleware(['auth:api'])
    ->prefix('v1')
    ->group(function () {
        // Exercise Management (Admin/Instructor)
        Route::apiResource('assessments/exercises', ExerciseController::class);
        Route::put('assessments/exercises/{exercise}/publish', [
            ExerciseController::class,
            'publish',
        ])->name('exercises.publish');
        Route::get('assessments/exercises/{exercise}/questions', [
            ExerciseController::class,
            'getQuestions',
        ])->name('exercises.questions');

        // Question Management
        Route::post('assessments/exercises/{exercise}/questions', [
            QuestionController::class,
            'store',
        ])->name('questions.store');
        Route::get('assessments/questions/{question}', [QuestionController::class, 'show'])->name(
            'questions.show',
        );
        Route::put('assessments/questions/{question}', [QuestionController::class, 'update'])->name(
            'questions.update',
        );
        Route::delete('assessments/questions/{question}', [QuestionController::class, 'destroy'])->name(
            'questions.destroy',
        );

        // Question Options Management
        Route::post('assessments/questions/{question}/options', [
            QuestionOptionController::class,
            'store',
        ])->name('options.store');
        Route::put('assessments/options/{option}', [QuestionOptionController::class, 'update'])->name(
            'options.update',
        );
        Route::delete('assessments/options/{option}', [
            QuestionOptionController::class,
            'destroy',
        ])->name('options.destroy');

        // Attempt Management (User)
        Route::post('assessments/exercises/{exercise}/attempts', [
            AttemptController::class,
            'store',
        ])->name('attempts.start');
        Route::get('assessments/attempts', [AttemptController::class, 'index'])->name('attempts.index');
        Route::get('assessments/attempts/{attempt}', [AttemptController::class, 'show'])->name(
            'attempts.show',
        );
        Route::post('assessments/attempts/{attempt}/answers', [
            AttemptController::class,
            'submitAnswer',
        ])->name('attempts.answer');
        Route::put('assessments/attempts/{attempt}/complete', [
            AttemptController::class,
            'complete',
        ])->name('attempts.complete');

        // Grading (Instructor/Admin)
        Route::get('assessments/exercises/{exercise}/attempts', [
            GradingController::class,
            'getExerciseAttempts',
        ])->name('grading.exercise-attempts');
        Route::get('assessments/attempts/{attempt}/answers', [
            GradingController::class,
            'getAttemptAnswers',
        ])->name('grading.attempt-answers');
        Route::put('assessments/answers/{answer}/feedback', [
            GradingController::class,
            'addFeedback',
        ])->name('grading.feedback');
        Route::put('assessments/attempts/{attempt}/score', [
            GradingController::class,
            'updateAttemptScore',
        ])->name('grading.update-score');

        // Assessment Registration
        Route::post('assessments/{assessment}/register', [
            AssessmentRegistrationController::class,
            'register',
        ])->name('assessments.register');
        Route::get('assessments/{assessment}/prerequisites', [
            AssessmentRegistrationController::class,
            'checkPrerequisites',
        ])->name('assessments.prerequisites');
        Route::get('assessments/{assessment}/slots', [
            AssessmentRegistrationController::class,
            'getAvailableSlots',
        ])->name('assessments.slots');
        Route::delete('assessment-registrations/{registration}', [
            AssessmentRegistrationController::class,
            'cancel',
        ])->name('assessment-registrations.cancel');
    });
