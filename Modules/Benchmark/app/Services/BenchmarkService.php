<?php

declare(strict_types=1);

namespace Modules\Benchmark\Services;

use Modules\Benchmark\Repositories\BenchmarkRepository;

class BenchmarkService
{
    public function __construct(
        protected readonly BenchmarkRepository $repository
    ) {}

    /**
     * Baseline: Just return a static array.
     * Logic: None.
     * Database: 0 queries.
     */
    public function baseline(): array
    {
        return [
            'status' => 'ok',
            'type' => 'baseline',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Light Workload: Fetch 1 user, simulation of mapping.
     * Logic: Light.
     * Database: 1 query.
     */
    public function light(): array
    {
        $user = $this->repository->getLightData();

        return [
            'status' => 'ok',
            'type' => 'light',
            'data' => [
                'id' => $user?->id,
                'name_length' => strlen($user?->name ?? ''),
                'random_calc' => sqrt(mt_rand(1, 10000)),
            ],
        ];
    }

    /**
     * Heavy Workload: Fetch 500 users, loop, sort, aggregate.
     * Logic: Heavy CPU usage (in PHP terms).
     * Database: 1 query (with eager load).
     */
    public function heavy(): array
    {
        $users = $this->repository->getHeavyData();

        // Simulate heavy processing: manual sorting and aggregation
        // This is intentionally inefficient to burn CPU cycles
        $sortedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'email' => $user->email,
                'score' => crc32($user->email ?? '') % 100, // Synthetic score
                'role_count' => $user->roles->count(),
            ];
        })->sortByDesc('score')->values();

        $totalScore = $sortedUsers->sum('score');
        $averageScore = $users->count() > 0 ? $totalScore / $users->count() : 0;

        return [
            'status' => 'ok',
            'type' => 'heavy',
            'count' => $users->count(),
            'aggregations' => [
                'total_score' => $totalScore,
                'average_score' => $averageScore,
            ],
            'top_5_ids' => $sortedUsers->take(5)->pluck('id'),
        ];
    }
}
