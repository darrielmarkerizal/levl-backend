<?php

declare(strict_types=1);

namespace Modules\Schemes\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Lesson;

class LessonPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->unit?->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('Student')) {
            return \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin') || $user->hasRole('Admin') || $user->hasRole('Instructor');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        return $user->hasRole('Instructor') && $lesson->unit?->course?->instructor_id === $user->id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return $user->hasRole('Admin') || ($user->hasRole('Instructor') && $lesson->unit?->course?->instructor_id === $user->id);
    }

    public function reorder(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    public function manageContent(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
