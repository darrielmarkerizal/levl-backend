<?php

declare(strict_types=1);

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gamification\Contracts\Services\LeaderboardServiceInterface;

class LeaderboardSeeder extends Seeder
{
    public function __construct(
        protected LeaderboardServiceInterface $leaderboardService
    ) {}

    public function run(): void
    {
        $this->command->info('Calculating and Seeding Leaderboards...');

        try {
            $this->leaderboardService->updateRankings();
            $this->command->info('✅ Leaderboards (Global & Course) updated successfully.');
        } catch (\Exception $e) {
            $this->command->error('❌ Failed to update leaderboards: ' . $e->getMessage());
        }
    }
}
