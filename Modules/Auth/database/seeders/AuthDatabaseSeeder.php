<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds the Auth module with:
     * 1. Role and Permission setup
     * 2. 1000+ comprehensive users with all status types
     * 3. Privacy settings for each user
     * 4. JWT refresh tokens for active users
     * 5. OTP codes for verification flows
     * 6. Password reset tokens
     * 7. Profile audit logs
     * 8. Login activities
     * 9. User activities
     */
    public function run(): void
    {
        $this->command->info('Starting Auth module seeding...');

        $this->call([
            RolePermissionSeeder::class,
        ]);

        $this->command->info('Auth module seeding with comprehensive data...');

        $this->call([
            UserSeeder::class,
            JwtRefreshTokenSeeder::class,
            OtpCodeSeeder::class,
            PasswordResetTokenSeeder::class,
            ProfileAuditLogSeeder::class,
        ]);

        $this->command->info('✅ Auth module seeding completed!');
    }
}
