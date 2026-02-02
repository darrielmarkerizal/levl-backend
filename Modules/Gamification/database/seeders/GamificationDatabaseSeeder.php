<?php

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;

class GamificationDatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        $this->call([
            MilestoneSeeder::class,
            BadgeSeeder::class,
            ChallengeSeeder::class,
            UserGamificationSeeder::class,
            LeaderboardSeeder::class,
        ]);
    }
}
