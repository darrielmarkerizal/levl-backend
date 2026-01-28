<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Ensures all users have complete profile data:
     * - Privacy settings
     * - User activities based on role and enrollments
     * - Activity tracking for learning progress
     */
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');
        
        echo "Seeding profile data for all users...\n";

        $users = User::with('privacySettings', 'roles')
            ->whereNull('deleted_at')
            ->get();

        if ($users->isEmpty()) {
            echo "⚠️  No users found. Skipping profile seeding.\n";
            return;
        }

        $privacyCount = 0;
        $createdAt = now()->toDateTimeString();
        $visibilities = [
            ProfilePrivacySetting::VISIBILITY_PUBLIC,
            ProfilePrivacySetting::VISIBILITY_PRIVATE,
        ];

        $privacySettings = [];
        foreach ($users as $user) {
            if (!$user->privacySettings) {
                $isAdmin = $user->roles->whereIn('name', ['Superadmin', 'Admin', 'Instructor'])->count() > 0;
                $visibility = $isAdmin 
                    ? ProfilePrivacySetting::VISIBILITY_PUBLIC 
                    : $visibilities[array_rand($visibilities)];

                $privacySettings[] = [
                    'user_id' => $user->id,
                    'profile_visibility' => $visibility,
                    'show_email' => rand(1, 100) <= 20,
                    'show_phone' => rand(1, 100) <= 10,
                    'show_activity_history' => true,
                    'show_achievements' => true,
                    'show_statistics' => true,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $privacyCount++;
                
                if (count($privacySettings) >= 200) {
                    \Illuminate\Support\Facades\DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);
                    $privacySettings = [];
                    gc_collect_cycles();
                }
            }
        }

        if (!empty($privacySettings)) {
            \Illuminate\Support\Facades\DB::table('profile_privacy_settings')->insertOrIgnore($privacySettings);
        }

        $activeUserIds = $users->filter(fn ($user) => $user->status === 'active')->pluck('id')->toArray();
        unset($users);
        gc_collect_cycles();

        $activityCount = $this->createUserActivitiesBatch($activeUserIds);

        echo "✅ Created $privacyCount privacy settings\n";
        echo "✅ Created $activityCount user activities\n";
        echo "✅ Profile seeding completed!\n";
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    /**
     * Batch create user activities for active users
     */
private function createUserActivitiesBatch(array $userIds): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('user_activities')) {
            return 0;
        }

        if (empty($userIds)) {
            return 0;
        }

        try {
            $activityTypes = [
                UserActivity::TYPE_ENROLLMENT,
                UserActivity::TYPE_COMPLETION,
                UserActivity::TYPE_SUBMISSION,
                UserActivity::TYPE_ACHIEVEMENT,
                UserActivity::TYPE_BADGE_EARNED,
                UserActivity::TYPE_CERTIFICATE_EARNED,
            ];
            
            $relatedTypes = [null, 'Course', 'Lesson', 'Quiz', 'Assignment'];
            $pregenTitles = ['Enrolled in course', 'Completed lesson', 'Submitted assignment', 'Earned badge', 'Achieved milestone', 'Started quiz', 'Finished module'];
            $pregenDescriptions = ['Progress made', 'New achievement', 'Course completed', 'Badge earned', 'Activity recorded', 'Learning milestone'];
            $createdAt = now()->toDateTimeString();

            $activities = [];
            $totalCount = 0;

            foreach ($userIds as $userId) {
                $numActivities = rand(3, 8);

                for ($i = 0; $i < $numActivities; $i++) {
                    $activities[] = [
                        'user_id' => $userId,
                        'activity_type' => $activityTypes[array_rand($activityTypes)],
                        'activity_data' => json_encode([
                            'title' => $pregenTitles[array_rand($pregenTitles)],
                            'description' => $pregenDescriptions[array_rand($pregenDescriptions)],
                            'points' => rand(10, 100),
                        ]),
                        'related_type' => $relatedTypes[array_rand($relatedTypes)],
                        'related_id' => rand(0, 1) ? rand(1, 100) : null,
                        'created_at' => $createdAt,
                    ];
                    $totalCount++;
                }
                
                if (count($activities) >= 500) {
                    \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
                    $activities = [];
                    gc_collect_cycles();
                }
            }

            if (!empty($activities)) {
                \Illuminate\Support\Facades\DB::table('user_activities')->insertOrIgnore($activities);
            }

            return $totalCount;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
    

