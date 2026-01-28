<?php
declare(strict_types=1);

namespace Modules\Enrollments\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Enrollments\Enums\ProgressStatus;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        \DB::connection()->disableQueryLog();
        
        $this->command->info("Seeding enrollments and progress...");

        // ✅ Use raw SQL for minimal memory footprint
        $studentIds = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Student')
            ->limit(800)
            ->pluck('users.id')
            ->toArray();
        
        $courseIds = \DB::table('courses')
            ->where('status', 'published')
            ->pluck('id')
            ->toArray();

        if (empty($studentIds) || empty($courseIds)) {
            echo "⚠️  No students or courses found. Skipping enrollment seeding.\n";
            return;
        }

        $this->command->info("Creating enrollments for " . count($studentIds) . " students...");

        // Pre-fetch existing enrollments
        $existingPairs = \DB::table('enrollments')
            ->get(['user_id', 'course_id'])
            ->map(fn($r) => $r->user_id . ':' . $r->course_id)
            ->flip()
            ->all();

        $enrollments = [];
        $enrollmentBatchSize = 300;
        $totalEnrollments = 0;

        foreach ($studentIds as $studentId) {
            $enrollmentCount = rand(3, 8);
            $selectedCourses = array_rand(array_flip($courseIds), min($enrollmentCount, count($courseIds)));
            $selectedCourses = is_array($selectedCourses) ? $selectedCourses : [$selectedCourses];

            foreach ($selectedCourses as $courseId) {
                $pairKey = $studentId . ':' . $courseId;
                if (isset($existingPairs[$pairKey])) {
                    continue;
                }

                $statusRandom = rand(1, 100);
                $status = match (true) {
                    $statusRandom <= 70 => 'active',
                    $statusRandom <= 85 => 'pending',
                    default => 'completed'
                };

                $enrollments[] = [
                    'user_id' => $studentId,
                    'course_id' => $courseId,
                    'status' => $status,
                    'enrolled_at' => now()->subDays(rand(1, 180)),
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $totalEnrollments++;

                if (count($enrollments) >= $enrollmentBatchSize) {
                    \DB::table('enrollments')->insertOrIgnore($enrollments);
                    $this->command->info("  ✅ Inserted $totalEnrollments enrollments");
                    
                    $enrollments = [];
                    unset($enrollments);
                    $enrollments = [];
                    
                    if ($totalEnrollments % 1000 === 0) {
                        gc_collect_cycles();
                    }
                }
            }
        }

        if (!empty($enrollments)) {
            \DB::table('enrollments')->insertOrIgnore($enrollments);
            unset($enrollments);
        }

        $this->command->info("✅ Created $totalEnrollments enrollments");
        gc_collect_cycles();

        // ✅ Create progress records using chunked processing
        $this->command->info("Creating progress records...");
        $this->createProgressRecordsChunked();

        $this->command->info("✅ Enrollment seeding completed!");
        
        gc_collect_cycles();
        \DB::connection()->enableQueryLog();
    }

    private function createProgressRecordsChunked(): void
    {
        $chunkSize = 50; // Small chunks to avoid memory issues
        $totalProcessed = 0;

        \DB::table('enrollments')
            ->orderBy('id')
            ->chunk($chunkSize, function ($enrollmentChunk) use (&$totalProcessed) {
                $courseProgress = [];
                $unitProgress = [];
                $lessonProgress = [];

                foreach ($enrollmentChunk as $enrollment) {
                    // Course Progress
                    $courseProgress[] = [
                        'enrollment_id' => $enrollment->id,
                        'status' => $this->mapStatus($enrollment->status),
                        'progress_percent' => $enrollment->status === 'completed' ? 100 : rand(0, 100),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Unit Progress (raw SQL to avoid loading models)
                    if (\Schema::hasTable('unit_progress')) {
                        $unitIds = \DB::table('units')
                            ->where('course_id', $enrollment->course_id)
                            ->pluck('id')
                            ->toArray();

                        foreach ($unitIds as $unitId) {
                            $unitProgress[] = [
                                'enrollment_id' => $enrollment->id,
                                'unit_id' => $unitId,
                                'status' => 'not_started',
                                'progress_percent' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        unset($unitIds);
                    }

                    // Lesson Progress (limit to 30 per enrollment to save memory)
                    if (\Schema::hasTable('lesson_progress')) {
                        $lessonIds = \DB::table('lessons')
                            ->join('units', 'lessons.unit_id', '=', 'units.id')
                            ->where('units.course_id', $enrollment->course_id)
                            ->limit(30)
                            ->pluck('lessons.id')
                            ->toArray();

                        foreach ($lessonIds as $lessonId) {
                            $lessonProgress[] = [
                                'enrollment_id' => $enrollment->id,
                                'lesson_id' => $lessonId,
                                'status' => 'not_started',
                                'progress_percent' => 0,
                                'attempt_count' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        unset($lessonIds);
                    }
                }

                // Insert in batches
                if (!empty($courseProgress)) {
                    foreach (array_chunk($courseProgress, 300) as $chunk) {
                        \DB::table('course_progress')->insertOrIgnore($chunk);
                    }
                }

                if (!empty($unitProgress)) {
                    foreach (array_chunk($unitProgress, 300) as $chunk) {
                        \DB::table('unit_progress')->insertOrIgnore($chunk);
                    }
                }

                if (!empty($lessonProgress)) {
                    foreach (array_chunk($lessonProgress, 300) as $chunk) {
                        \DB::table('lesson_progress')->insertOrIgnore($chunk);
                    }
                }

                $totalProcessed += count($enrollmentChunk);
                unset($courseProgress, $unitProgress, $lessonProgress);
                
                if ($totalProcessed % 200 === 0) {
                    gc_collect_cycles();
                    $this->command->info("   ✓ Created progress for $totalProcessed enrollments");
                }
            });
    }

    private function mapStatus(string|ProgressStatus $enrollmentStatus): string
    {
        $status = is_string($enrollmentStatus) ? $enrollmentStatus : $enrollmentStatus->value;

        return match ($status) {
            'active' => ProgressStatus::InProgress->value,
            'completed' => ProgressStatus::Completed->value,
            default => ProgressStatus::NotStarted->value,
        };
    }
}
