<?php

namespace Modules\Forums\Repositories;

use Carbon\Carbon;
use Modules\Forums\Models\ForumStatistic;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ForumStatisticsRepository
{
    /**
     * Get or create statistics for a scheme and period.
     */
    public function getOrCreate(int $schemeId, Carbon $periodStart, Carbon $periodEnd, ?int $userId = null): ForumStatistic
    {
        return ForumStatistic::firstOrCreate(
            [
                'scheme_id' => $schemeId,
                'user_id' => $userId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'threads_count' => 0,
                'replies_count' => 0,
                'views_count' => 0,
            ]
        );
    }

    /**
     * Update statistics for a scheme.
     */
    public function updateSchemeStatistics(int $schemeId, Carbon $periodStart, Carbon $periodEnd): ForumStatistic
    {
        $statistic = $this->getOrCreate($schemeId, $periodStart, $periodEnd);

        // Count threads
        $threadsCount = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        // Count replies
        $repliesCount = Reply::whereHas('thread', function ($query) use ($schemeId) {
            $query->where('scheme_id', $schemeId);
        })
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        // Sum views
        $viewsCount = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum('views_count');

        // Calculate response rate
        $responseRate = $this->calculateResponseRate($schemeId, $periodStart, $periodEnd);

        // Calculate average response time
        $avgResponseTime = $this->calculateAverageResponseTime($schemeId, $periodStart, $periodEnd);

        $statistic->update([
            'threads_count' => $threadsCount,
            'replies_count' => $repliesCount,
            'views_count' => $viewsCount,
            'response_rate' => $responseRate,
            'avg_response_time_minutes' => $avgResponseTime,
        ]);

        return $statistic;
    }

    /**
     * Update statistics for a user in a scheme.
     */
    public function updateUserStatistics(int $schemeId, int $userId, Carbon $periodStart, Carbon $periodEnd): ForumStatistic
    {
        $statistic = $this->getOrCreate($schemeId, $periodStart, $periodEnd, $userId);

        // Count user's threads
        $threadsCount = Thread::forScheme($schemeId)
            ->where('author_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        // Count user's replies
        $repliesCount = Reply::whereHas('thread', function ($query) use ($schemeId) {
            $query->where('scheme_id', $schemeId);
        })
            ->where('author_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $statistic->update([
            'threads_count' => $threadsCount,
            'replies_count' => $repliesCount,
        ]);

        return $statistic;
    }

    /**
     * Calculate response rate for a scheme.
     * Response rate = (threads with replies / total threads) * 100
     */
    public function calculateResponseRate(int $schemeId, Carbon $periodStart, Carbon $periodEnd): float
    {
        $totalThreads = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        if ($totalThreads === 0) {
            return 0.0;
        }

        $threadsWithReplies = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('replies_count', '>', 0)
            ->count();

        return round(($threadsWithReplies / $totalThreads) * 100, 2);
    }

    /**
     * Calculate average response time in minutes.
     */
    public function calculateAverageResponseTime(int $schemeId, Carbon $periodStart, Carbon $periodEnd): ?int
    {
        $threads = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with(['replies' => function ($query) {
                $query->orderBy('created_at', 'asc')->limit(1);
            }])
            ->get();

        $responseTimes = [];

        foreach ($threads as $thread) {
            if ($thread->replies->isNotEmpty()) {
                $firstReply = $thread->replies->first();
                $responseTime = $thread->created_at->diffInMinutes($firstReply->created_at);
                $responseTimes[] = $responseTime;
            }
        }

        if (empty($responseTimes)) {
            return null;
        }

        return (int) round(array_sum($responseTimes) / count($responseTimes));
    }

    /**
     * Get statistics for a scheme.
     */
    public function getSchemeStatistics(int $schemeId, Carbon $periodStart, Carbon $periodEnd): ?ForumStatistic
    {
        return ForumStatistic::forScheme($schemeId)
            ->schemeWide()
            ->forPeriod($periodStart, $periodEnd)
            ->first();
    }

    /**
     * Get statistics for a user in a scheme.
     */
    public function getUserStatistics(int $schemeId, int $userId, Carbon $periodStart, Carbon $periodEnd): ?ForumStatistic
    {
        return ForumStatistic::forScheme($schemeId)
            ->forUser($userId)
            ->forPeriod($periodStart, $periodEnd)
            ->first();
    }
}
