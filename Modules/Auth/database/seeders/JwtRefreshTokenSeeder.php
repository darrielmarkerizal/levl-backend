<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\JwtRefreshToken;
use Modules\Auth\Models\User;

class JwtRefreshTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates JWT refresh tokens for users:
     * - Active users: 2-5 valid tokens (multi-device)
     * - Pending users: 0 tokens
     * - Inactive users: 1 revoked token
     * - Banned users: 1 revoked token
     */
    public function run(): void
    {
        echo "Creating JWT refresh tokens...\n";

        // ✅ Load all users at once (no query per user)
        $users = User::whereNull('deleted_at')->get();
        
        if ($users->isEmpty()) {
            echo "⚠️  No users found. Skipping JWT token seeding.\n";
            return;
        }

        $tokens = [];
        $count = 0;

        foreach ($users as $user) {
            $tokenCount = match ($user->status) {
                'active' => rand(2, 5),
                'pending' => 0,
                'inactive' => 1,
                'banned' => 1,
                default => 0,
            };

            if ($tokenCount === 0) {
                continue;
            }

            for ($i = 0; $i < $tokenCount; $i++) {
                // ✅ Build token data for batch insert
                $tokens[] = [
                    'user_id' => $user->id,
                    'device_id' => hash('sha256', fake()->ipv4() . fake()->userAgent() . $user->id),
                    'token' => hash('sha256', \Illuminate\Support\Str::random(64)),
                    'ip' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'last_used_at' => now()->subDays(rand(0, 7)),
                    'idle_expires_at' => now()->addDays(14),
                    'absolute_expires_at' => now()->addDays(90),
                    'revoked_at' => in_array($user->status, ['inactive', 'banned']) 
                        ? now()->subDays(rand(1, 30)) 
                        : null,
                    'replaced_by' => null,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];
                $count++;
            }
        }

        // ✅ Batch insert all tokens at once
        if (!empty($tokens)) {
            foreach (array_chunk($tokens, 1000) as $chunk) {
                \Illuminate\Support\Facades\DB::table('jwt_refresh_tokens')->insertOrIgnore($chunk);
            }
        }

        echo "✅ Created $count JWT refresh tokens\n";
    }
}
