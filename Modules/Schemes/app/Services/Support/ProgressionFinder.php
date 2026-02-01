<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Schemes\Models\Course;

class ProgressionFinder
{
    public function getEnrollmentForCourse(int $courseId, int $userId): ?Enrollment
    {
        return Enrollment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->whereIn('status', [
                EnrollmentStatus::Active,
                EnrollmentStatus::Completed,
            ])
            ->first();
    }

    public function validateAndGetProgress(Course $course, int $targetUserId, int $requestingUserId): array
    {
        $targetUser = \Modules\Auth\Models\User::find($targetUserId);
        if (!$targetUser) {
            throw new ModelNotFoundException(__('messages.users.not_found'));
        }

        $enrollment = Enrollment::where('user_id', $targetUserId)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (!$enrollment) {
            throw new ModelNotFoundException(__('messages.progress.enrollment_not_found'));
        }

        return $this->getCourseProgressData($course, $enrollment);
    }

    public function getProgressForUser(Course $course, int $userId): array
    {
        $enrollment = $this->getEnrollmentForCourse($course->id, $userId);
        if (!$enrollment) {
            throw new ModelNotFoundException(__('messages.progress.enrollment_not_found'));
        }

        return $this->getCourseProgressData($course, $enrollment);
    }

    public function getCourseProgressData(Course $course, Enrollment $enrollment): array
    {
        $courseModel = $course->fresh([
            'units' => function ($query) {
                $query->where('status', 'published')
                    ->orderBy('order')
                    ->with(['lessons' => function ($lessonQuery) {
                        $lessonQuery->where('status', 'published')->orderBy('order');
                    }]);
            },
        ]);

        if (! $courseModel) {
            return [];
        }

        // We need to trigger updates here to ensure data is fresh, but that belongs in StateProcessor.
        // However, the original service mixed this.
        // Ideally, reading progress shouldn't write.
        // But the original code called `updateUnitProgress` inside `getCourseProgressData`.
        // To decouple, we should extract the update logic.
        // For now, I will assume the caller (Processor) handles updates if needed, OR I will inject Processor here?
        // No, cyclic dependency.
        // I will duplicate the calculation logic strictly for "reading" or better,
        // The `update*Progress` methods in original service actually recalculated and SAVED.
        // If I move `update*Progress` to Processor, Finder cannot call them easily without circular dep.
        // Solution: `ProgressionStateProcessor` should serve this method `getCourseProgressData` because it involves calculation/updates?
        // OR `getCourseProgressData` should effectively be a "Calculate and Get".
        // Let's look at `getCourseProgressData` in original.
        // It does DB::transaction and calls `updateUnitProgress`.
        // So it IS a state-mutating read.
        // I will move `getCourseProgressData` to `ProgressionStateProcessor` instead of Finder,
        // OR I will ask `ProgressionStateProcessor` to do the calculation.
        // But `ProgressionStateProcessor` depends on calculating.
        // Let's put `getCourseProgressData` in `ProgressionStateProcessor` as it writes to DB.
        // `ProgressionFinder` will primarily be for simple lookups like `getEnrollmentForCourse`.
        
        // Wait, `validateAndGetProgress` calls `getCourseProgressData`.
        // So `validateAndGetProgress` also belongs in Processor? Or it delegates.
        // I'll keep `getEnrollmentForCourse` here.
        // I'll return empty array or basic data here?
        // No, I'll move `getCourseProgressData` and usages to `ProgressionStateProcessor` or a dedicated `ProgressionCalculator`.
        // The plan said: "Extract Calculation Logic to ProgressionCalculator" / "ProgressionStateProcessor".
        // `ProgressionStateProcessor` handles `markLessonCompleted`, `update*Progress`.
        // `getCourseProgressData` calls `updateUnitProgress`.
        // So `getCourseProgressData` belongs in `ProgressionStateProcessor`.
        
        return []; // Placeholder, method will be moved.
    }
}
