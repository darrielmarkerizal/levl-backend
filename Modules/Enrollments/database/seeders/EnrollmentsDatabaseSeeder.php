<?php

namespace Modules\Enrollments\Database\Seeders;

use Illuminate\Database\Seeder;

class EnrollmentsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds the Enrollments module with:
     * 1. 500-800 student enrollments in courses
     * 2. Progress tracking for each enrollment
     * 3. Unit and lesson progress
     */
    public function run(): void
    {
        $this->call([
            EnrollmentSeeder::class,
        ]);
    }
}
