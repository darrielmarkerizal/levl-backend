<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;

class LearningDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds the Learning module with:
     * 1. 5-8 assignments per lesson
     * 2. Multiple submissions per student
     * 3. Various submission statuses
     */
    public function run(): void
    {
        $this->call([
            AssignmentAndSubmissionSeeder::class,
        ]);
    }
}
