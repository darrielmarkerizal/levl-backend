<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

class ProfilePrivacySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates privacy settings for users that don't have them
     */
    public function run(): void
    {
        echo "Creating profile privacy settings...\n";

        $users = User::whereDoesntHave('privacySettings')->get();
        $count = 0;

        foreach ($users as $user) {
            $visibility = match ($user->status) {
                'pending' => fake()->randomElement(['public', 'private']),
                'active' => fake()->randomElement(['public', 'friends_only', 'private']),
                'inactive' => 'private',
                'banned' => 'private',
                default => 'public',
            };

            ProfilePrivacySetting::create([
                'user_id' => $user->id,
                'profile_visibility' => $visibility,
                'show_email' => fake()->boolean(30),
                'show_phone' => fake()->boolean(20),
                'show_activity_history' => fake()->boolean(70),
                'show_achievements' => fake()->boolean(80),
                'show_statistics' => fake()->boolean(60),
            ]);

            $count++;
        }

        echo "✅ Created $count profile privacy settings\n";
    }
}
