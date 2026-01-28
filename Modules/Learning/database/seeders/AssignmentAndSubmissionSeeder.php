<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Lesson;

class AssignmentAndSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates comprehensive assignment and submission data:
     * - 5-8 assignments per lesson
     * - 50-70% students submit assignments
     * - Multiple submissions per student (resubmissions)
     * - Various submission statuses (draft, submitted, graded)
     */
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        
        echo "Seeding assignments and submissions...\n";

        // ✅ Eager load relationships to avoid N+1
        $lessons = Lesson::with('unit.course')->get();
        
        // ✅ Pre-fetch instructors once (don't query per lesson)
        $instructors = User::whereHas('roles', function ($q) {
            $q->where('name', 'Instructor');
        })->pluck('id')->toArray();

        if ($lessons->isEmpty()) {
            echo "⚠️  No lessons found. Skipping assignment seeding.\n";
            return;
        }

        if (empty($instructors)) {
            echo "⚠️  No instructors found. Skipping assignment seeding.\n";
            return;
        }

        // ✅ Pre-fetch all active enrollments (don't query per submission)
        $enrollments = Enrollment::where('status', 'active')
            ->with('user', 'course')
            ->get();

        if ($enrollments->isEmpty()) {
            echo "⚠️  No enrollments found. Skipping assignment seeding.\n";
            return;
        }

        echo "Creating assignments for lessons...\n";
        $assignmentCount = 0;
        $submissionCount = 0;

        $assignments = [];
        $submissions = [];

        foreach ($lessons as $lesson) {
            $assignmentsPerLesson = rand(5, 8);

            // ✅ Pre-allocate instructor (don't query per assignment)
            $instructorId = $instructors[array_rand($instructors)];

            for ($i = 0; $i < $assignmentsPerLesson; $i++) {
                $assignments[] = [
                    'lesson_id' => $lesson->id,
                    'title' => fake()->sentence(5),
                    'description' => fake()->paragraph(),
                    'created_by' => $instructorId,
                    'max_score' => rand(50, 100),
                    'deadline_at' => now()->addDays(rand(7, 30)),
                    'status' => 'published',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $assignmentCount++;
            }
        }

        $startTime = now()->subSecond(); // Buffer for strict equality

        // ✅ Batch insert assignments
        if (!empty($assignments)) {
            foreach (array_chunk($assignments, 1000) as $chunk) {
                \Illuminate\Support\Facades\DB::table('assignments')->insert($chunk);
            }
        }

        // ✅ Get inserted assignments with eager loaded lesson relationships
        $assignmentModels = Assignment::with('lesson.unit.course')
            ->where('created_at', '>=', $startTime)
            ->get();

        // ✅ Pre-calculate submission data grouped by course
        $enrollmentsByCourse = $enrollments->groupBy('course_id');

        $processedAssignments = 0;
        foreach ($assignmentModels as $assignment) {
            $processedAssignments++;
            if ($processedAssignments % 5000 === 0) {
                gc_collect_cycles();
                echo "      ✓ Processed $processedAssignments assignments\n";
            }
            
            $courseId = $assignment->lesson->unit->course_id;
            $courseEnrollments = $enrollmentsByCourse->get($courseId, collect());

            // ✅ 50-70% of students submit assignments
            $submissionRate = rand(50, 70);
            $studentsToSubmit = intval($courseEnrollments->count() * $submissionRate / 100);
            $selectedEnrollments = $courseEnrollments->random(min($studentsToSubmit, $courseEnrollments->count()));

            foreach ($selectedEnrollments as $enrollment) {
                // ... (generation logic)
                $statusRandom = rand(1, 100);
                $status = match (true) {
                    $statusRandom <= 30 => 'submitted',
                    $statusRandom <= 70 => 'graded',
                    default => 'draft',
                };

                $submissions[] = [
                    'assignment_id' => $assignment->id,
                    'user_id' => $enrollment->user_id,
                    'enrollment_id' => $enrollment->id,
                    'answer_text' => fake()->paragraphs(3, true),
                    'status' => $status,
                    'score' => $status === 'graded' ? rand(0, $assignment->max_score) : null,
                    'submitted_at' => $status !== 'draft' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $submissionCount++;

                if (count($submissions) >= 1000) {
                    \Illuminate\Support\Facades\DB::table('submissions')->insertOrIgnore($submissions);
                    $submissions = [];
                }
            }
        }

        // Insert remaining submissions
        if (!empty($submissions)) {
            \Illuminate\Support\Facades\DB::table('submissions')->insertOrIgnore($submissions);
        }

        echo "✅ Assignment and submission seeding completed!\n";
        echo "Created $assignmentCount assignments with $submissionCount submissions\n";
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }
}
