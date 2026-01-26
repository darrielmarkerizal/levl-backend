<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\OtpCode;
use Modules\Auth\Models\User;

class OtpCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates OTP codes for various purposes:
     * - Email verification codes for pending users
     * - Password reset tokens for some users
     * - Account deletion tokens for some users
     */
    public function run(): void
    {
        echo "Creating OTP codes...\n";

        $count = 0;
        $pendingUsers = User::where('status', 'pending')->get();

        // Create email verification OTPs for pending users
        foreach ($pendingUsers as $user) {
            OtpCode::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'register_verification',
                'code' => 'magic',
                'meta' => [
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                ],
                'expires_at' => now()->addMinutes(60),
                'consumed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        // Create some password reset tokens (20% of active users)
        $activeUsers = User::where('status', 'active')->inRandomOrder()->limit((int) (User::count() * 0.2))->get();

        foreach ($activeUsers as $user) {
            if (fake()->boolean(20)) {
                OtpCode::create([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'channel' => 'email',
                    'provider' => 'mailhog',
                    'purpose' => 'password_reset',
                    'code' => 'magic',
                    'meta' => [
                        'token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
                    ],
                    'expires_at' => now()->addMinutes(60),
                    'consumed_at' => null,
                    'created_at' => now()->subHours(1),
                    'updated_at' => now()->subHours(1),
                ]);
                $count++;
            }
        }

        // Create email change verification tokens (5% of active users)
        $usersForEmailChange = User::where('status', 'active')->inRandomOrder()->limit((int) (User::count() * 0.05))->get();

        foreach ($usersForEmailChange as $user) {
            OtpCode::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'email_change_verification',
                'code' => 'magic',
                'meta' => [
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                    'new_email' => fake()->email(),
                ],
                'expires_at' => now()->addMinutes(60),
                'consumed_at' => null,
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subHours(1),
            ]);
            $count++;
        }

        // Create account deletion tokens (2% of active users)
        $usersForDeletion = User::where('status', 'active')->inRandomOrder()->limit((int) (User::count() * 0.02))->get();

        foreach ($usersForDeletion as $user) {
            OtpCode::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $user->id,
                'channel' => 'email',
                'provider' => 'mailhog',
                'purpose' => 'account_deletion',
                'code' => 'magic',
                'meta' => [
                    'token_hash' => hash('sha256', \Illuminate\Support\Str::random(16)),
                ],
                'expires_at' => now()->addMinutes(60),
                'consumed_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
            $count++;
        }

        echo "✅ Created $count OTP codes\n";
    }
}
