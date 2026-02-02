<?php

declare(strict_types=1);

namespace Modules\Gamification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Gamification\Models\UserGamificationStat;
use Modules\Gamification\Models\Point;
use Modules\Gamification\Models\Badge;
use Modules\Gamification\Models\UserBadge;
use Modules\Gamification\Models\Challenge;
use Modules\Gamification\Models\UserChallengeAssignment;
use Modules\Gamification\Models\UserScopeStat;
use Modules\Schemes\Models\Course;
use Modules\Gamification\Enums\PointSourceType;
use Modules\Gamification\Enums\PointReason;
use Modules\Gamification\Enums\ChallengeAssignmentStatus;

class UserGamificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding User Gamification Stats...');

        $users = User::all();
        $badges = Badge::all();
        $challenges = Challenge::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping User Gamification Seeding.');
            return;
        }

        foreach ($users as $user) {
            // 1. Create or Update Gamification Stats
            // Randomize activity level: 0=Inactive, 1=Beginner, 2=Active, 3=Power User
            $activityLevel = rand(0, 3);
            
            if ($activityLevel === 0) {
                // Inactive user, maybe just initialized stats
                UserGamificationStat::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'total_xp' => 0,
                        'global_level' => 1,
                        'current_streak' => 0,
                        'longest_streak' => 0,
                        'last_activity_date' => null,
                        'stats_updated_at' => now(),
                    ]
                );
                continue;
            }

            // Active users
            $xp = rand(100, 5000) * $activityLevel;
            $level = max(1, (int)($xp / 500)); // Rough estimation
            $streak = rand(0, 10 * $activityLevel);
            
            UserGamificationStat::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'total_xp' => $xp,
                    'global_level' => $level,
                    'current_streak' => $streak,
                    'longest_streak' => max($streak, rand($streak, $streak + 5)),
                    'last_activity_date' => now()->subDays(rand(0, 3)),
                    'stats_updated_at' => now(),
                ]
            );

            // 2. Generate Point History (Last 5-10 entries to simulate history)
            $validSourceTypes = array_filter(PointSourceType::cases(), fn($type) => $type !== PointSourceType::Grade);

            for ($i = 0; $i < rand(5, 15); $i++) {
                Point::create([
                    'user_id' => $user->id,
                    'source_type' => $validSourceTypes[array_rand($validSourceTypes)],
                    'source_id' => 0, // Placeholder
                    'points' => rand(10, 100),
                    'reason' => PointReason::cases()[array_rand(PointReason::cases())],
                    'description' => 'Simulated activity reward',
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }

            // 3. Assign Random Badges
            if ($badges->isNotEmpty()) {
                $earnedBadges = $badges->random(min($badges->count(), rand(0, 3)));
                foreach ($earnedBadges as $badge) {
                    UserBadge::firstOrCreate([
                        'user_id' => $user->id,
                        'badge_id' => $badge->id,
                    ], [
                        'earned_at' => now()->subDays(rand(1, 60)),
                    ]);
                }
            }

            // 4. Assign Challenges
            if ($challenges->isNotEmpty()) {
                $assignedChallenges = $challenges->random(min($challenges->count(), rand(1, 2)));
                foreach ($assignedChallenges as $challenge) {
                    $status = rand(0, 1) ? ChallengeAssignmentStatus::Completed : ChallengeAssignmentStatus::InProgress;
                    
                    UserChallengeAssignment::firstOrCreate([
                        'user_id' => $user->id,
                        'challenge_id' => $challenge->id,
                    ], [
                        'current_progress' => rand(0, $challenge->target_count ?? 10),
                        'status' => $status,
                        'assigned_date' => now()->subDays(rand(1, 7)),
                        'completed_at' => $status === ChallengeAssignmentStatus::Completed ? now() : null,
                    ]);
                }
            }

            // 5. Generate Course-Specific Stats (UserScopeStat)
            // Assign user to 1-3 random courses
            $courses = Course::inRandomOrder()->limit(rand(1, 3))->get();
            foreach ($courses as $course) {
                $courseXp = rand(50, min($xp, 2000));
                $courseLevel = max(1, (int)($courseXp / 500));
                
                UserScopeStat::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'scope_type' => 'course',
                        'scope_id' => $course->id,
                    ],
                    [
                        'total_xp' => $courseXp,
                        'current_level' => $courseLevel,
                    ]
                );
            }
        }

        $this->command->info('✅ User Gamification Stats & Scope Stats seeded for ' . $users->count() . ' users.');
    }
}
