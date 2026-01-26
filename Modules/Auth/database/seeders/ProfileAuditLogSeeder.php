<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\ProfileAuditLog;
use Modules\Auth\Models\User;

class ProfileAuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates audit logs for profile changes
     */
    public function run(): void
    {
        echo "Creating profile audit logs...\n";

        // ✅ Load active users once (no query per user)
        $activeUsers = User::where('status', 'active')
            ->limit(500)
            ->get();

        if ($activeUsers->isEmpty()) {
            echo "⚠️  No active users found. Skipping profile audit log seeding.\n";
            return;
        }

        $auditLogs = [];
        $count = 0;

        foreach ($activeUsers as $user) {
            $logCount = fake()->numberBetween(1, 10);

            for ($i = 0; $i < $logCount; $i++) {
                $action = fake()->randomElement([
                    'profile_update',
                    'avatar_upload',
                    'avatar_delete',
                    'email_change',
                    'password_change',
                    'privacy_settings_update',
                ]);

                $changes = match ($action) {
                    'profile_update' => [
                        'name' => [fake()->name(), fake()->name()],
                        'bio' => [fake()->sentence(), fake()->sentence()],
                    ],
                    'email_change' => [
                        'email' => [fake()->email(), fake()->email()],
                    ],
                    'password_change' => [
                        'password' => ['***', '***'],
                    ],
                    'privacy_settings_update' => [
                        'profile_visibility' => [fake()->randomElement(['public', 'private', 'friends_only']), 'public'],
                        'show_email' => [fake()->boolean(), fake()->boolean()],
                    ],
                    default => ['field' => [fake()->word(), fake()->word()]],
                };

                // ✅ Build audit log data for batch insert
                $auditLogs[] = [
                    'user_id' => $user->id,
                    'action' => $action,
                    'changes' => json_encode($changes),
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => now()->subDays(rand(1, 365)),
                ];
                $count++;
            }
        }

        // ✅ Batch insert all audit logs at once
        if (!empty($auditLogs)) {
            foreach (array_chunk($auditLogs, 1000) as $chunk) {
                \Illuminate\Support\Facades\DB::table('profile_audit_logs')->insertOrIgnore($chunk);
            }
        }

        echo "✅ Created $count profile audit logs\n";
    }
}
