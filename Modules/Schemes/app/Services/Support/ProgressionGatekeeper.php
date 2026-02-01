<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Auth\Models\User;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Enrollments\Enums\ProgressStatus;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Models\LessonProgress;
use Modules\Enrollments\Models\UnitProgress;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;

class ProgressionGatekeeper
{
    public function __construct(
        private readonly ProgressionFinder $finder
    ) {}

    public function validateLessonAccess(Course $course, Unit $unit, Lesson $lesson, int $userId): Enrollment
    {
        if ($unit->course_id !== $course->id || $lesson->unit_id !== $unit->id) {
            throw new ModelNotFoundException(__('messages.progress.lesson_not_in_unit'));
        }

        $user = User::find($userId);
        
        if ($user && ($user->hasRole(['Superadmin', 'Admin', 'Instructor']))) {
            $enrollment = $this->finder->getEnrollmentForCourse($course->id, $userId);
            if (!$enrollment) {
                // Auto-enroll staff
                $enrollment = Enrollment::create([
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'status' => EnrollmentStatus::Active,
                    'enrolled_at' => now(),
                ]);
            }
            return $enrollment;
        }

        $enrollment = $this->finder->getEnrollmentForCourse($course->id, $userId);
        
        if (!$enrollment || !$this->canAccessLesson($lesson, $enrollment)) {
            throw new \App\Exceptions\BusinessException(__('messages.progress.locked_prerequisite'), [], 403);
        }

        return $enrollment;
    }

    public function canAccessLesson(Lesson $lesson, Enrollment $enrollment): bool
    {
        $lessonModel = $lesson->fresh([
            'unit.course',
            'unit.lessons' => function ($query) {
                $query->where('status', 'published')->orderBy('order');
            },
        ]);

        if (! $lessonModel || ! $lessonModel->unit || ! $lessonModel->unit->course) {
            return false;
        }

        $course = $lessonModel->unit->course;
        if ($course->progression_mode === 'free') {
            return true;
        }

        $orderedUnits = $course->units()
            ->where('status', 'published')
            ->orderBy('order')
            ->get(['id']);

        foreach ($orderedUnits as $courseUnit) {
            if ((int) $courseUnit->id === (int) $lessonModel->unit->id) {
                break;
            }

            $unitStatus = UnitProgress::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('unit_id', $courseUnit->id)
                ->value('status');

            if ($unitStatus !== ProgressStatus::Completed) {
                return false;
            }
        }

        $lessons = $lessonModel->unit->lessons ?? new EloquentCollection;
        if ($lessons->isEmpty()) {
            return true;
        }

        $progressMap = LessonProgress::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        foreach ($lessons as $unitLesson) {
            if ((int) $unitLesson->id === (int) $lessonModel->id) {
                return true;
            }

            if (($progressMap->get($unitLesson->id)?->status ?? ProgressStatus::NotStarted) !== ProgressStatus::Completed) {
                return false;
            }
        }

        return true;
    }
}
