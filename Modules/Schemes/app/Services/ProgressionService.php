<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\Support\ProgressionFinder;
use Modules\Schemes\Services\Support\ProgressionGatekeeper;
use Modules\Schemes\Services\Support\ProgressionStateProcessor;

class ProgressionService
{
    public function __construct(
        private readonly ProgressionFinder $finder,
        private readonly ProgressionGatekeeper $gatekeeper,
        private readonly ProgressionStateProcessor $stateProcessor
    ) {}

    public function validateLessonAccess(Course $course, Unit $unit, Lesson $lesson, int $userId): Enrollment
    {
        return $this->gatekeeper->validateLessonAccess($course, $unit, $lesson, $userId);
    }

    public function getProgressForUser(Course $course, int $userId): array
    {
        // This was in Finder, but since it mutates state via getCourseProgressData, 
        // effectively we can assume the user wants the calculated state.
        // We can delegate lookup to Finder then process in StateProcessor if needed.
        // But `ProgressionStateProcessor` has `getCourseProgressData` which calculates.
        // Let's implement it here by coordinating.
        
        $enrollment = $this->finder->getEnrollmentForCourse($course->id, $userId);
        if (!$enrollment) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.progress.enrollment_not_found'));
        }

        return $this->stateProcessor->getCourseProgressData($course, $enrollment);
    }

    public function validateAndGetProgress(Course $course, int $targetUserId, int $requestingUserId): array
    {
        // Similar coordination
        // Original Finder method was just lookup + getCourseProgressData
        
        $targetUser = \Modules\Auth\Models\User::find($targetUserId);
        if (!$targetUser) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.users.not_found'));
        }

        $enrollment = $this->finder->getEnrollmentForCourse($course->id, $targetUserId);
        
        if (!$enrollment) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.progress.enrollment_not_found'));
        }

        return $this->stateProcessor->getCourseProgressData($course, $enrollment);
    }

    public function getEnrollmentForCourse(int $courseId, int $userId): ?Enrollment
    {
        return $this->finder->getEnrollmentForCourse($courseId, $userId);
    }

    public function markLessonCompleted(Lesson $lesson, Enrollment $enrollment): void
    {
        $this->stateProcessor->markLessonCompleted($lesson, $enrollment);
    }

    public function onLessonCompleted(Lesson $lesson, Enrollment $enrollment): void
    {
        $this->stateProcessor->markLessonCompleted($lesson, $enrollment);
    }

    public function markLessonUncompleted(Lesson $lesson, Enrollment $enrollment): void
    {
        $this->stateProcessor->markLessonUncompleted($lesson, $enrollment);
    }

    public function markUnitCompleted(Unit $unit, Enrollment $enrollment): void
    {
        $this->stateProcessor->markUnitCompleted($unit, $enrollment);
    }

    public function canAccessLesson(Lesson $lesson, Enrollment $enrollment): bool
    {
        return $this->gatekeeper->canAccessLesson($lesson, $enrollment);
    }

    public function getCourseProgressData(Course $course, Enrollment $enrollment): array
    {
        return $this->stateProcessor->getCourseProgressData($course, $enrollment);
    }
}
