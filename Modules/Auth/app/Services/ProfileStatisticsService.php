<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\Services\ProfileStatisticsServiceInterface;
use Modules\Auth\Models\User;

class ProfileStatisticsService implements ProfileStatisticsServiceInterface
{
    public function getStatistics(User $user): array
    {
        return Cache::remember("user_statistics_{$user->id}", 300, function () use ($user) {
            return [
                'enrollments' => $this->getEnrollmentStats($user),
                'performance' => $this->getPerformanceStats($user),
                'activity' => $this->getActivityStats($user),
            ];
        });
    }

    public function getEnrollmentStats(User $user): array
    {
        $enrollments = DB::table('enrollments')
            ->where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
            ")
            ->first();

        return [
            'total_enrolled' => $enrollments->total_enrolled ?? 0,
            'total_completed' => $enrollments->total_completed ?? 0,
            'in_progress' => $enrollments->in_progress ?? 0,
        ];
    }

    public function getPerformanceStats(User $user): array
    {
        $completionRate = $this->calculateCompletionRate($user);
        $averageScore = $this->calculateAverageScore($user);

        return [
            'completion_rate' => $completionRate,
            'average_score' => $averageScore,
        ];
    }

    public function getActivityStats(User $user): array
    {
        $recentActivities = DB::table('user_activities')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'activities_last_30_days' => $recentActivities,
        ];
    }

    public function calculateCompletionRate(User $user): float
    {
        $stats = $this->getEnrollmentStats($user);

        if ($stats['total_enrolled'] === 0) {
            return 0.0;
        }

        return round(($stats['total_completed'] / $stats['total_enrolled']) * 100, 2);
    }

    public function calculateAverageScore(User $user): float
    {
        $averageScore = DB::table('submissions')
            ->where('user_id', $user->id)
            ->where('status', 'graded')
            ->avg('score');

        return (float) round($averageScore ?? 0, 2);
    }

}
