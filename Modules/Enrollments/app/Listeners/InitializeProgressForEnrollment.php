<?php

declare(strict_types=1);

namespace Modules\Enrollments\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Events\EnrollmentCreated;

class InitializeProgressForEnrollment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EnrollmentCreated $event): void
    {
        $enrollmentId = $event->enrollment->id;
        $courseId = $event->enrollment->course_id;
        $status = $event->enrollment->status;

        $this->initializeProgress($enrollmentId, $courseId);

        if ($status === EnrollmentStatus::Completed) {
            DB::table('enrollments')
                ->where('id', $enrollmentId)
                ->update([
                    'status' => EnrollmentStatus::Active->value,
                    'completed_at' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    private function initializeProgress(int $enrollmentId, int $courseId): void
    {
        $now = now()->toDateTimeString();

        DB::transaction(function () use ($enrollmentId, $courseId, $now) {
            $units = DB::table('units')
                ->select('id')
                ->where('course_id', $courseId)
                ->whereNull('deleted_at')
                ->get();

            if ($units->isNotEmpty()) {
                $unitRecords = $units->map(fn ($unit) => [
                    'enrollment_id' => $enrollmentId,
                    'unit_id' => $unit->id,
                    'status' => 'not_started',
                    'progress_percent' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                foreach (array_chunk($unitRecords, 1000) as $chunk) {
                    DB::table('unit_progress')->insertOrIgnore($chunk);
                }
            }

            $lessons = DB::table('lessons')
                ->select('lessons.id')
                ->join('units', 'lessons.unit_id', '=', 'units.id')
                ->where('units.course_id', $courseId)
                ->where('lessons.status', 'published')
                ->whereNull('lessons.deleted_at')
                ->whereNull('units.deleted_at')
                ->get();

            if ($lessons->isNotEmpty()) {
                $lessonRecords = $lessons->map(fn ($lesson) => [
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lesson->id,
                    'status' => 'not_started',
                    'progress_percent' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                foreach (array_chunk($lessonRecords, 1000) as $chunk) {
                    DB::table('lesson_progress')->insertOrIgnore($chunk);
                }
            }

            DB::table('course_progress')->insertOrIgnore([
                'enrollment_id' => $enrollmentId,
                'status' => 'not_started',
                'progress_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }
}
