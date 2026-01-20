<?php

declare(strict_types=1);

namespace Modules\Enrollments\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;

class EnrollmentPolicy
{
    /**
     * Determine if the user can view any enrollment
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    /**
     * Determine if the user can modify the enrollment
     */
    public function modify(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can modify any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // User can modify their own enrollment
        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can cancel the enrollment
     */
    public function cancel(User $user, Enrollment $enrollment): bool
    {
        return $this->modify($user, $enrollment);
    }

    /**
     * Determine if the user can withdraw from the enrollment
     */
    public function withdraw(User $user, Enrollment $enrollment): bool
    {
        return $this->modify($user, $enrollment);
    }


    /**
     * Determine if the user can view the enrollment
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can view any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // User can view their own enrollment
        if ((int) $enrollment->user_id === (int) $user->id) {
            return true;
        }

        // Course managers can view enrollments for their courses
        if ($enrollment->course) {
            if ($user->hasAnyRole(['Admin', 'Instructor'])) {
                return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
            }
        }

        return false;
    }

    /**
     * Determine if the user can approve the enrollment
     */
    /**
     * Determine if the user can approve the enrollment
     */
    public function approve(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can approve any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Course managers can approve enrollments
        return $this->isCourseManager($user, $enrollment);
    }

    /**
     * Determine if the user can decline the enrollment
     */
    public function decline(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can decline any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Course managers can decline enrollments
        return $this->isCourseManager($user, $enrollment);
    }

    /**
     * Determine if the user can remove the enrollment
     */
    public function remove(User $user, Enrollment $enrollment): bool
    {
        // Superadmin can remove any enrollment
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        // Course managers can remove enrollments
        return $this->isCourseManager($user, $enrollment);
    }

    /**
     * Helper to check if user manages the course
     */
    private function isCourseManager(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->course) {
            return false;
        }

        if ($user->hasAnyRole(['Admin', 'Instructor'])) {
            return $enrollment->course->hasInstructor($user) || $enrollment->course->hasAdmin($user);
        }

        return false;
    }
}
