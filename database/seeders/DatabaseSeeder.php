<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  /**
   * Seed the application's database.
   * 
   * Seeds all modules with comprehensive, related data:
   * 1. Auth: 1000+ users with all status types
   * 2. Schemes: 50 courses with units and lessons
   * 3. Enrollments: 500-800 student enrollments
   * 4. Learning: Assignments and submissions
   * 5. Grading: Grades and appeals
   */
  public function run(): void
  {
    $this->call([
      RoleSeeder::class,
      SuperAdminSeeder::class,
      ActiveUsersSeeder::class,
      \Modules\Common\Database\Seeders\SystemSettingSeeder::class,
      \Modules\Common\Database\Seeders\CategorySeeder::class,
      MasterDataSeeder::class,
      // Auth module comprehensive seeding (1000+ users with all statuses)
      \Modules\Auth\Database\Seeders\AuthComprehensiveDataSeeder::class,
      // Auth profile data (privacy settings and activities)
      \Modules\Auth\Database\Seeders\ProfileSeeder::class,
      // Schemes module (courses with units and lessons)
      \Modules\Schemes\Database\Seeders\SchemesDatabaseSeeder::class,
      // Enrollments module (student enrollments and progress)
      \Modules\Enrollments\Database\Seeders\EnrollmentsDatabaseSeeder::class,
      // Learning module (assignments and submissions)
      \Modules\Learning\Database\Seeders\LearningDatabaseSeeder::class,
      // Grading module (grades and appeals)
      \Modules\Grading\Database\Seeders\GradingDatabaseSeeder::class,
    ]);
  }
}

