<?php

namespace Modules\Schemes\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Schemes\Database\Factories\CourseFactory;
use Modules\Schemes\Database\Factories\LessonFactory;
use Modules\Schemes\Database\Factories\UnitFactory;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Models\Lesson;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates comprehensive course data:
     * - 50 courses with multiple units and lessons
     * - Assign 200 instructors to courses (Admin & Instructor roles)
     * - Create units (5-8 per course)
     * - Create lessons (10-15 per unit)
     */
    public function run(): void
    {
        echo "Seeding courses and course structure...\n";

        $instructors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Instructor', 'Admin', 'Superadmin']);
        })->limit(200)->get();

        if ($instructors->isEmpty()) {
            echo "⚠️  No instructors found. Skipping course seeding.\n";
            return;
        }

        // Create 50 courses
        echo "Creating 50 courses...\n";
        $courses = Course::factory()
            ->count(50)
            ->published()
            ->openEnrollment()
            ->create();

        // ✅ Batch assign instructors to courses (instead of per-instructor updateOrCreate)
        echo "Assigning instructors to courses...\n";
        $this->assignInstructorsToCourses($courses, $instructors);

        // ✅ Batch create units and lessons
        echo "Creating units and lessons...\n";
        $this->createUnitsAndLessons($courses);

        echo "✅ Course seeding completed!\n";
        echo "Created 50 courses with units and lessons\n";
    }

    /**
     * Batch assign instructors to courses
     */
    private function assignInstructorsToCourses($courses, $instructors): void
    {
        $courseAdmins = [];

        foreach ($courses as $course) {
            $courseInstructors = $instructors->random(rand(1, 3));
            foreach ($courseInstructors as $instructor) {
                $courseAdmins[] = [
                    'course_id' => $course->id,
                    'user_id' => $instructor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // ✅ Single batch insert instead of multiple updateOrCreate calls
        if (!empty($courseAdmins)) {
            \Illuminate\Support\Facades\DB::table('course_admins')->insertOrIgnore($courseAdmins);
        }
    }

    /**
     * Batch create units and lessons for all courses
     */
    private function createUnitsAndLessons($courses): void
    {
        foreach ($courses as $course) {
            $unitCount = rand(5, 8);
            
            // ✅ Create all units at once
            $units = Unit::factory()
                ->count($unitCount)
                ->forCourse($course)
                ->create();

            // ✅ Batch create lessons per unit
            foreach ($units as $unit) {
                $lessonCount = rand(10, 15);
                Lesson::factory()
                    ->count($lessonCount)
                    ->forUnit($unit)
                    ->create();
            }
        }
    }
}
