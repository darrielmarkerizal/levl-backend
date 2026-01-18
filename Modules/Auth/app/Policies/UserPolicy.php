<?php

declare(strict_types=1);

namespace Modules\Auth\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole(['Superadmin', 'Admin']);
    }

    public function view(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('Superadmin')) {
            return true;
        }

        if (!$authUser->hasRole('Admin')) {
            return false;
        }

        // Admin can view other Admins (but not Superadmin)
        if ($targetUser->hasRole('Admin') && !$targetUser->hasRole('Superadmin')) {
            return true;
        }

        // Admin can view Instructors/Students in courses they manage
        if ($targetUser->hasRole(['Instructor', 'Student'])) {
            $managedCourseIds = CourseAdmin::where('user_id', $authUser->id)
                ->pluck('course_id')
                ->unique();

            return Enrollment::where('user_id', $targetUser->id)
                ->whereIn('course_id', $managedCourseIds)
                ->exists();
        }

        return false;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasRole(['Superadmin', 'Admin']);
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $this->view($authUser, $targetUser);
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        if (!$authUser->hasRole('Superadmin')) {
            return false;
        }

        // Cannot delete self
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        return true;
    }

    public function updateStatus(User $authUser, User $targetUser): bool
    {
        return $this->view($authUser, $targetUser);
    }
}
