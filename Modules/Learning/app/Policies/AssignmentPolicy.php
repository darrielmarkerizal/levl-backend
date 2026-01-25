<?php

declare(strict_types=1);

namespace Modules\Learning\Policies;

use Modules\Auth\Models\User;
use Modules\Learning\Models\Assignment;

class AssignmentPolicy
{
    private function isCourseAdmin(User $user, int $courseId): bool
    {
        return cache()->tags(['course-admin'])->remember(
            "course_admin:{$user->id}:{$courseId}",
            3600, // 1 hour
            function () use ($user, $courseId) {
                $course = \Modules\Schemes\Models\Course::find($courseId);
                if (!$course) {
                    return false;
                }

                return $course->admins()->where('user_id', $user->id)->exists();
            }
        );
    }
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Assignment $assignment): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        if (!$courseId) {
            return false;
        }

        if ($user->hasRole('Instructor')) {
            $assignment->loadMissing('lesson.unit.course');
            return $assignment->lesson?->unit?->course?->instructor_id === $user->id;
        }

        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return false;
    }

    public function resolveCourseFromAssignment(Assignment $assignment): ?\Modules\Schemes\Models\Course
    {
        $courseId = $assignment->getCourseId();
        if (!$courseId) {
            return null;
        }
        return \Modules\Schemes\Models\Course::find($courseId);
    }

    public function create(User $user, \Modules\Schemes\Models\Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $this->resolveCourseFromAssignment($assignment);
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $this->resolveCourseFromAssignment($assignment);
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }

    public function grantOverride(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $course = $this->resolveCourseFromAssignment($assignment);
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    public function viewOverrides(User $user, Assignment $assignment): bool
    {
        return $this->grantOverride($user, $assignment);
    }

    public function duplicate(User $user, Assignment $assignment): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }

    /**
     * Determine if user can view questions for this assignment.
     * Only course managers (Admin/Instructor) can view questions.
     * Students cannot view questions of assignments they haven't started.
     */
    public function listQuestions(User $user, Assignment $assignment): bool
    {
        // Superadmins can always view
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Students cannot view assignment questions
        if ($user->hasRole('Student')) {
            return false;
        }

        $courseId = $assignment->getCourseId();
        
        if (!$courseId) {
            return false;
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        
        if (!$course) {
            return false;
        }

        // Admins can view if they manage the course
        if ($user->hasRole('Admin')) {
            return $this->isCourseAdmin($user, $course->id);
        }

        // Instructors can view if they created the course
        return $user->hasRole('Instructor') && $course->instructor_id === $user->id;
    }
}
