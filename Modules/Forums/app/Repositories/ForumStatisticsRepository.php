<?php

namespace Modules\Forums\Repositories;

use Carbon\Carbon;
use Modules\Forums\Models\ForumStatistic;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ForumStatisticsRepository
{
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

    public function updateSchemeStatistics(int $schemeId, Carbon $periodStart, Carbon $periodEnd): ForumStatistic
    {
        $statistic = $this->getOrCreate($schemeId, $periodStart, $periodEnd);

        $threadsCount = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $repliesCount = Reply::whereHas('thread', function ($query) use ($schemeId) {
            $query->where('course_id', $schemeId);
        })
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $viewsCount = Thread::forScheme($schemeId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum('views_count');

        $responseRate = $this->calculateResponseRate($schemeId, $periodStart, $periodEnd);

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

    public function updateUserStatistics(int $schemeId, int $userId, Carbon $periodStart, Carbon $periodEnd): ForumStatistic
    {
        $statistic = $this->getOrCreate($schemeId, $periodStart, $periodEnd, $userId);

        $threadsCount = Thread::forScheme($schemeId)
            ->where('author_id', $userId)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $repliesCount = Reply::whereHas('thread', function ($query) use ($schemeId) {
            $query->where('course_id', $schemeId);
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

    public function getSchemeStatistics(int $schemeId, Carbon $periodStart, Carbon $periodEnd): ?ForumStatistic
    {
        return ForumStatistic::forScheme($schemeId)
            ->schemeWide()
            ->forPeriod($periodStart, $periodEnd)
            ->first();
    }

    public function getUserStatistics(int $schemeId, int $userId, Carbon $periodStart, Carbon $periodEnd): ?ForumStatistic
    {
        return ForumStatistic::forScheme($schemeId)
            ->forUser($userId)
            ->forPeriod($periodStart, $periodEnd)
            ->first();
    }
}
