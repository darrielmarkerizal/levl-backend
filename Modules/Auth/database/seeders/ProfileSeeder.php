<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Create privacy settings if not exists
            if (! $user->privacySettings) {
                ProfilePrivacySetting::create([
                    'user_id' => $user->id,
                    'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
                    'show_email' => false,
                    'show_phone' => false,
                    'show_activity_history' => true,
                    'show_achievements' => true,
                    'show_statistics' => true,
                ]);
            }

            // Create sample activities
            $activityTypes = [
                UserActivity::TYPE_ENROLLMENT,
                UserActivity::TYPE_COMPLETION,
                UserActivity::TYPE_SUBMISSION,
                UserActivity::TYPE_BADGE_EARNED,
            ];

            foreach ($activityTypes as $type) {
                UserActivity::create([
                    'user_id' => $user->id,
                    'activity_type' => $type,
                    'activity_data' => [
                        'sample' => true,
                        'description' => "Sample {$type} activity",
                    ],
                ]);
            }
        }

        $this->command->info('Profile data seeded successfully!');
    }
}
