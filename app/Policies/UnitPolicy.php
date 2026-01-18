<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Unit;

class UnitPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Unit $unit)
    {
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

    public function create(User $user, int $courseId)
    {
        if ($user->hasRole('Superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }

        $course = \Modules\Schemes\Models\Course::find($courseId);
        if (! $course) {
            return $this->deny(__('messages.course_not_found'));
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }

    public function update(User $user, Unit $unit)
    {
        if ($user->hasRole('Superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny(__('messages.course_not_found'));
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }

    public function delete(User $user, Unit $unit)
    {
        if ($user->hasRole('Superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }

        $course = $unit->course;
        if (! $course) {
            return $this->deny(__('messages.course_not_found'));
        }

        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }

        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }
}
