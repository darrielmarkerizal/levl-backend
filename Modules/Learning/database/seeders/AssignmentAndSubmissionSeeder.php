<?php

namespace Modules\Learning\Database\Seeders;

use Illuminate\Database\Seeder;

class AssignmentAndSubmissionSeeder extends Seeder
{
    private array $pregenTitles = [];
    private array $pregenDescriptions = [];
    private array $pregenAnswers = [];
    private string $createdAt;
    private string $deadlineAt;

    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        ini_set('memory_limit', '1536M');
        
        echo "Seeding assignments and submissions...\n";

        $this->pregenerateFakeData();
        $this->createdAt = now()->toDateTimeString();
        $this->deadlineAt = now()->addDays(14)->toDateTimeString();

        $lessonCount = \DB::table('lessons')->count();
        $instructorIds = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Instructor')
            ->pluck('users.id')
            ->toArray();

        if ($lessonCount === 0) {
            echo "⚠️  No lessons found. Skipping assignment seeding.\n";
            return;
        }

        if (empty($instructorIds)) {
            echo "⚠️  No instructors found. Skipping assignment seeding.\n";
            return;
        }

        echo "Creating assignments for $lessonCount lessons...\n";
        
        $assignmentCount = 0;
        $assignments = [];
        $assignmentBatchSize = 100;
        $processedLessons = 0;

        foreach (\DB::table('lessons')->select('id')->orderBy('id')->cursor() as $lesson) {
            $processedLessons++;
            
            if (rand(1, 100) > 60) continue;
            
            $assignmentsPerLesson = rand(1, 2);
            $instructorId = $instructorIds[array_rand($instructorIds)];

            for ($i = 0; $i < $assignmentsPerLesson; $i++) {
                $assignments[] = [
                    'lesson_id' => $lesson->id,
                    'title' => $this->pregenTitles[array_rand($this->pregenTitles)],
                    'description' => $this->pregenDescriptions[array_rand($this->pregenDescriptions)],
                    'created_by' => $instructorId,
                    'max_score' => rand(50, 100),
                    'deadline_at' => $this->deadlineAt,
                    'status' => 'published',
                    'created_at' => $this->createdAt,
                    'updated_at' => $this->createdAt,
                ];
                $assignmentCount++;
                
                if (count($assignments) >= $assignmentBatchSize) {
                    \DB::table('assignments')->insertOrIgnore($assignments);
                    $assignments = null;
                    unset($assignments);
                    $assignments = [];
                    gc_collect_cycles();
                    
                    if ($assignmentCount % 1000 === 0) {
                        echo "   ✓ Created $assignmentCount assignments\n";
                    }
                }
            }
            
            if ($processedLessons % 500 === 0) {
                gc_collect_cycles();
            }
        }

        if (!empty($assignments)) {
            \DB::table('assignments')->insertOrIgnore($assignments);
            unset($assignments);
        }

        echo "✅ Created $assignmentCount assignments\n";
        gc_collect_cycles();

        echo "Creating submissions...\n";
        $submissionCount = 0;
        $submissions = [];
        $submissionBatchSize = 30;
        $processedAssignments = 0;

        $assignmentCourseMap = \DB::table('assignments')
            ->join('lessons', 'assignments.lesson_id', '=', 'lessons.id')
            ->join('units', 'lessons.unit_id', '=', 'units.id')
            ->select('assignments.id', 'assignments.max_score', 'units.course_id')
            ->get()
            ->keyBy('id')
            ->toArray();

        $courseEnrollments = [];
        foreach (\DB::table('enrollments')->where('status', 'active')->get(['user_id', 'id', 'course_id']) as $e) {
            if (!isset($courseEnrollments[$e->course_id])) {
                $courseEnrollments[$e->course_id] = [];
            }
            $courseEnrollments[$e->course_id][$e->user_id] = $e->id;
        }

        $assignmentIds = array_keys($assignmentCourseMap);
        shuffle($assignmentIds);
        $assignmentIds = array_slice($assignmentIds, 0, (int)(count($assignmentIds) * 0.15));

        echo "Processing " . count($assignmentIds) . " assignments (15% of total)...\n";

        foreach ($assignmentIds as $assignmentId) {
            $processedAssignments++;
            
            $assignmentData = $assignmentCourseMap[$assignmentId] ?? null;
            if (!$assignmentData) continue;
            
            $courseId = $assignmentData->course_id;
            $maxScore = $assignmentData->max_score;
            
            $enrollmentData = $courseEnrollments[$courseId] ?? [];
            if (empty($enrollmentData)) continue;
            
            $enrollmentKeys = array_keys($enrollmentData);
            shuffle($enrollmentKeys);
            $selectedUsers = array_slice($enrollmentKeys, 0, min(3, count($enrollmentKeys)));
            
            foreach ($selectedUsers as $userId) {
                $enrollmentId = $enrollmentData[$userId];
                
                $statusRandom = rand(1, 100);
                $status = match (true) {
                    $statusRandom <= 30 => 'submitted',
                    $statusRandom <= 70 => 'graded',
                    default => 'draft',
                };
                
                $submissions[] = [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'enrollment_id' => $enrollmentId,
                    'answer_text' => $this->pregenAnswers[array_rand($this->pregenAnswers)],
                    'status' => $status,
                    'score' => $status === 'graded' ? rand(0, $maxScore) : null,
                    'submitted_at' => $status !== 'draft' ? $this->createdAt : null,
                    'created_at' => $this->createdAt,
                    'updated_at' => $this->createdAt,
                ];
                $submissionCount++;
                
                if (count($submissions) >= $submissionBatchSize) {
                    \DB::table('submissions')->insertOrIgnore($submissions);
                    $submissions = null;
                    unset($submissions);
                    $submissions = [];
                    gc_collect_cycles();
                    
                    if ($submissionCount % 500 === 0) {
                        echo "  ✅ Inserted $submissionCount submissions\n";
                    }
                }
            }
            
            if ($processedAssignments % 50 === 0) {
                gc_collect_cycles();
            }
        }
        
        unset($assignmentCourseMap, $courseEnrollments, $assignmentIds);

        if (!empty($submissions)) {
            \DB::table('submissions')->insertOrIgnore($submissions);
            unset($submissions);
        }

        echo "✅ Assignment and submission seeding completed!\n";
        echo "Created $assignmentCount assignments with $submissionCount submissions\n";
        
        $this->pregenTitles = [];
        $this->pregenDescriptions = [];
        $this->pregenAnswers = [];
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    private function pregenerateFakeData(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        
        for ($i = 0; $i < 100; $i++) {
            $this->pregenTitles[] = $faker->sentence(3);
            $this->pregenDescriptions[] = $faker->text(80);
            $this->pregenAnswers[] = $faker->paragraph(1);
        }
        
        unset($faker);
    }
}

