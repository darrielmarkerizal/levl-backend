<?php

namespace Modules\Grading\Database\Seeders;

use Illuminate\Database\Seeder;

class GradingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds the Grading module with:
     * 1. Grades for submitted assignments
     * 2. Appeals with different statuses
     */
    public function run(): void
    {
        $this->call([
            GradeAndAppealSeeder::class,
        ]);
    }
}
