<?php

declare(strict_types=1);

namespace Modules\Schemes\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\LessonBlock;

class LessonBlockPolicy
{
    public function view(User $user, LessonBlock $block): bool
    {
        $lesson = $block->lesson;
        if (!$lesson) {
            return false;
        }

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

    public function update(User $user, LessonBlock $block): bool
    {
        $lesson = $block->lesson;
        if (!$lesson) return false;

        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return true;
        }

        return $user->hasRole('Instructor') && $lesson->unit?->course?->instructor_id === $user->id;
    }

    public function delete(User $user, LessonBlock $block): bool
    {
        return $this->update($user, $block);
    }
}
