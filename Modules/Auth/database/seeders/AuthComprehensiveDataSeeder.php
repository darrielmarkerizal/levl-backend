<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Auth Module Comprehensive Seeder
 * 
 * This seeder provides comprehensive test data for the Auth module.
 * It creates 1000+ users with various roles, statuses, and activity patterns.
 * 
 * Usage:
 *   php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthDatabaseSeeder"
 * 
 * Demo Credentials:
 *   - Superadmin: superadmin / password
 *   - Admin: admin / password
 *   - Instructor: instructor / password
 *   - Student: student / password
 * 
 * Data Distribution:
 *   Roles:
 *   - 50 Superadmin users
 *   - 100 Admin users
 *   - 200 Instructor users
 *   - 650 Student users
 *   Total: 1000 users
 * 
 *   User Status Distribution (per role):
 *   - 70% Active
 *   - 15% Pending
 *   - 10% Inactive
 *   - 5% Banned
 * 
 * Related Data Created:
 *   - Privacy settings for all users
 *   - Login activities (2,500+ records)
 *   - User activities (5,000+ records)
 *   - JWT refresh tokens (2,000+ records)
 *   - OTP codes for verification flows
 *   - Password reset tokens
 *   - Profile audit logs (3,000+ records)
 * 
 * @see UserSeeder
 * @see JwtRefreshTokenSeeder
 * @see OtpCodeSeeder
 * @see PasswordResetTokenSeeder
 * @see ProfileAuditLogSeeder
 * @see ProfilePrivacySettingSeeder
 * @see ProfileSeeder
 * @see RolePermissionSeeder
 */
class AuthComprehensiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════╗');
        $this->command->info('║  Auth Module Comprehensive Data Seeding     ║');
        $this->command->info('╚════════════════════════════════════════════╝');
        $this->command->info('');

        // Call all seeders in order
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            ProfilePrivacySettingSeeder::class,
            ProfileSeeder::class,
            JwtRefreshTokenSeeder::class,
            OtpCodeSeeder::class,
            PasswordResetTokenSeeder::class,
            ProfileAuditLogSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════╗');
        $this->command->info('║  ✅ Seeding Completed Successfully!         ║');
        $this->command->info('╚════════════════════════════════════════════╝');
        $this->command->info('');
        $this->printSummary();
        $this->command->info('');
    }

    /**
     * Print a summary of the seeded data
     */
    private function printSummary(): void
    {
        $this->command->info('📊 Data Summary:');
        $this->command->info('  • Total Users: 1,000+');
        $this->command->info('  • Demo Accounts: 6');
        $this->command->info('  • Privacy Settings: 1,000+');
        $this->command->info('  • Login Activities: 2,500+');
        $this->command->info('  • User Activities: 5,000+');
        $this->command->info('  • JWT Refresh Tokens: 2,000+');
        $this->command->info('  • OTP Codes: 100+');
        $this->command->info('  • Password Reset Tokens: 15+');
        $this->command->info('  • Profile Audit Logs: 3,000+');
        $this->command->info('');
        $this->command->info('🔐 Demo Credentials:');
        $this->command->info('  Email          | Username    | Password');
        $this->command->info('  ───────────────┼─────────────┼─────────');
        $this->command->info('  superadmin@... | superadmin  | password');
        $this->command->info('  admin@test.com | admin       | password');
        $this->command->info('  instructor@... | instructor  | password');
        $this->command->info('  student@test.. | student     | password');
        $this->command->info('');
        $this->command->info('🧪 Testing Scenarios:');
        $this->command->info('  ✓ Login with active users');
        $this->command->info('  ✓ Email verification flow (pending users)');
        $this->command->info('  ✓ Password reset flow');
        $this->command->info('  ✓ Account deletion flow');
        $this->command->info('  ✓ Multi-device token management');
        $this->command->info('  ✓ Role-based access control');
        $this->command->info('  ✓ Privacy settings filtering');
        $this->command->info('  ✓ Activity tracking and history');
        $this->command->info('');
    }
}
