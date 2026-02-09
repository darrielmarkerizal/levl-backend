<?php

use Illuminate\Support\Facades\Route;
use Modules\Forums\Http\Controllers\ForumDashboardController;
use Modules\Forums\Http\Controllers\ForumStatisticsController;
use Modules\Forums\Http\Controllers\ReactionController;
use Modules\Forums\Http\Controllers\ReplyController;
use Modules\Forums\Http\Controllers\ThreadController;
use Modules\Forums\Http\Middleware\CheckForumAccess;

Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    Route::prefix('forums')->group(function () {
        Route::get('threads', [ForumDashboardController::class, 'allThreads']);
        Route::get('threads/trending', [ForumDashboardController::class, 'trendingThreads']);
        Route::get('my-threads', [ForumDashboardController::class, 'myThreads']);
    });

    Route::prefix('courses/{course:slug}/forum')
        ->middleware(CheckForumAccess::class)
        ->group(function () {
            Route::apiResource('threads', ThreadController::class);
            Route::patch('threads/{thread}/pin', [ThreadController::class, 'pin']);
            Route::patch('threads/{thread}/unpin', [ThreadController::class, 'unpin']);
            Route::patch('threads/{thread}/close', [ThreadController::class, 'close']);
            Route::patch('threads/{thread}/open', [ThreadController::class, 'open']);
            Route::patch('threads/{thread}/resolve', [ThreadController::class, 'resolve']);
            Route::patch('threads/{thread}/unresolve', [ThreadController::class, 'unresolve']);

            Route::get('threads/{thread}/replies', [ReplyController::class, 'index']);
            Route::post('threads/{thread}/replies', [ReplyController::class, 'store']);
            Route::patch('threads/{thread}/replies/{reply}', [ReplyController::class, 'update']);
            Route::delete('threads/{thread}/replies/{reply}', [ReplyController::class, 'destroy']);

            Route::post('threads/{thread}/reactions', [ReactionController::class, 'storeThreadReaction']);
            Route::delete('threads/{thread}/reactions/{reaction}', [ReactionController::class, 'destroyThreadReaction']);
            Route::post('threads/{thread}/replies/{reply}/reactions', [ReactionController::class, 'storeReplyReaction']);
            Route::delete('threads/{thread}/replies/{reply}/reactions/{reaction}', [ReactionController::class, 'destroyReplyReaction']);

            Route::get('statistics', [ForumStatisticsController::class, 'index']);
            Route::get('my-statistics', [ForumStatisticsController::class, 'show']);
        });
});
