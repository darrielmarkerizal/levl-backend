<?php

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gamification\Models\Challenge;

class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        \DB::connection()->disableQueryLog();

        $this->command->info('Seeding 50 Challenges with Badges...');

        // Get available badges to assign randomly
        $badges = \Modules\Gamification\Models\Badge::all();
        $faker = \Faker\Factory::create('id_ID');

        $challengesToCreate = [];

        for ($i = 0; $i < 50; $i++) {
            $type = $faker->randomElement(['daily', 'weekly']);
            $points = $type === 'daily' ? $faker->numberBetween(10, 50) : $faker->numberBetween(100, 500);
            
            // Criteria templates
            $criteriaOptions = [
                ['type' => 'lessons_completed', 'target_range' => [1, 5], 'desc' => 'Selesaikan :target lesson'],
                ['type' => 'exercises_completed', 'target_range' => [1, 5], 'desc' => 'Selesaikan :target latihan soal'],
                ['type' => 'assignments_submitted', 'target_range' => [1, 3], 'desc' => 'Kumpulkan :target tugas'],
                ['type' => 'xp_earned', 'target_range' => [50, 500], 'desc' => 'Kumpulkan :target XP'],
            ];

            $selectedCriteria = $faker->randomElement($criteriaOptions);
            $target = $faker->numberBetween($selectedCriteria['target_range'][0], $selectedCriteria['target_range'][1]);
            
            // Adjust target higher for weekly
            if ($type === 'weekly') {
                $target = (int) ceil($target * 3.5);
            }

            $description = str_replace(':target', (string) $target, $selectedCriteria['desc']);
            if ($type === 'daily') $description .= ' hari ini.';
            else $description .= ' minggu ini.';

            // 40% chance to have a badge reward
            $badgeId = ($faker->boolean(40) && $badges->isNotEmpty()) 
                ? $badges->random()->id 
                : null;

            $challengesToCreate[] = [
                'title' => $faker->words(3, true) . ($type === 'daily' ? ' Harian' : ' Mingguan'),
                'description' => ucfirst($description),
                'type' => $type,
                'criteria' => json_encode([
                    'type' => $selectedCriteria['type'],
                    'target' => $target,
                ]),
                'target_count' => $target,
                'points_reward' => $points,
                'badge_id' => $badgeId,
                'start_at' => now(),
                'end_at' => $type === 'daily' ? now()->endOfDay() : now()->endOfWeek(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($challengesToCreate, 10) as $chunk) {
            Challenge::insert($chunk);
        }

        $this->command->info('✅ 50 Challenges seeded successfully!');
        
        // Count stats
        $badgeCount = Challenge::whereNotNull('badge_id')->count();
        $this->command->info("   - With Badges: $badgeCount");
        $this->command->info("   - Daily: " . Challenge::where('type', 'daily')->count());
        $this->command->info("   - Weekly: " . Challenge::where('type', 'weekly')->count());
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}
