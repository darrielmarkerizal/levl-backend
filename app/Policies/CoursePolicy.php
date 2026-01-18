<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class CoursePolicy
{
    use HandlesAuthorization;

    public function view(?User $user, Course $course)
    {
        return Response::allow();
    }

    public function create(User $user)
    {
        if ($user->hasRole('Superadmin') || $user->hasRole('Admin')) {
            return Response::allow();
        }
        return $this->deny(__('messages.admin_only'));
    }

    public function update(User $user, Course $course)
    {
        if ($user->hasRole('Superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
        }
        if ((int) $course->instructor_id === (int) $user->id) {
            return Response::allow();
        }
        if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
            return Response::allow();
        }

        return $this->deny(__('messages.course_owner_only'));
    }

    public function delete(User $user, Course $course)
    {
        if ($user->hasRole('Superadmin')) {
            return Response::allow();
        }
        if (! $user->hasRole('Admin')) {
            return $this->deny(__('messages.admin_only'));
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
