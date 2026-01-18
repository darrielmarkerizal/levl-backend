<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Lesson;

class LessonPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Lesson $lesson)
    {
        $unit = $lesson->unit;
        if (!$unit) {
            return $this->deny(__('messages.unit_not_found'));
        }

        $course = $unit->course;
        if (!$course) {
            return $this->deny(__('messages.course_not_found'));
        }

        if ($user->hasRole('Student')) {
            $isEnrolled = \Modules\Enrollments\Models\Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if (!$isEnrolled) {
                return $this->deny(__('messages.enrollment_required'));
            }
        }

        return Response::allow();
    }

    public function update(User $user, Lesson $lesson)
    {
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }

        $unit = $lesson->unit;
        if (! $unit) {
            return $this->deny(__('messages.unit_not_found'));
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny(__('messages.course_not_found'));
        }

        // Check if user is instructor or course admin
        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }

    public function delete(User $user, Lesson $lesson)
    {
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }

        $unit = $lesson->unit;
        if (! $unit) {
            return $this->deny(__('messages.unit_not_found'));
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny(__('messages.course_not_found'));
        }

        // Check if user is instructor or course admin
        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }
}
