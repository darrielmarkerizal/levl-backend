<?php

namespace Modules\Forums\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Thread;

class ThreadPolicy
{
    /**
     * Determine if the user can view the thread.
     */
    public function view(User $user, Thread $thread): bool
    {
        // User must be enrolled in the scheme
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $thread->scheme_id)
            ->exists();
    }

    /**
     * Determine if the user can create threads.
     */
    public function create(User $user, int $schemeId): bool
    {
        // User must be enrolled in the scheme
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $schemeId)
            ->exists();
    }

    /**
     * Determine if the user can update the thread.
     */
    public function update(User $user, Thread $thread): bool
    {
        // Only the author can update their own thread
        return $user->id === $thread->author_id;
    }

    /**
     * Determine if the user can delete the thread.
     */
    public function delete(User $user, Thread $thread): bool
    {
        // Author can delete their own thread, or moderators can delete any thread
        return $user->id === $thread->author_id || $this->isModerator($user, $thread->scheme_id);
    }

    /**
     * Determine if the user can pin the thread.
     */
    public function pin(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->scheme_id);
    }

    /**
     * Determine if the user can close the thread.
     */
    public function close(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->scheme_id);
    }

    /**
     * Check if user is a moderator (admin or instructor) for the scheme.
     */
    protected function isModerator(User $user, int $schemeId): bool
    {
        // Check if user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is instructor for this scheme
        if ($user->hasRole('instructor')) {
            // Check if instructor is assigned to this course
            return \Modules\Schemes\Models\Course::where('id', $schemeId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }
}
