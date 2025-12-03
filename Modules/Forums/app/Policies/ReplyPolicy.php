<?php

namespace Modules\Forums\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ReplyPolicy
{
    /**
     * Determine if the user can view the reply.
     */
    public function view(User $user, Reply $reply): bool
    {
        // User must be enrolled in the scheme
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $reply->thread->scheme_id)
            ->exists();
    }

    /**
     * Determine if the user can create replies.
     */
    public function create(User $user, Thread $thread): bool
    {
        // User must be enrolled in the scheme
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $thread->scheme_id)
            ->exists();
    }

    /**
     * Determine if the user can update the reply.
     */
    public function update(User $user, Reply $reply): bool
    {
        // Only the author can update their own reply
        return $user->id === $reply->author_id;
    }

    /**
     * Determine if the user can delete the reply.
     */
    public function delete(User $user, Reply $reply): bool
    {
        // Author can delete their own reply, or moderators can delete any reply
        return $user->id === $reply->author_id || $this->isModerator($user, $reply->thread->scheme_id);
    }

    /**
     * Determine if the user can mark a reply as accepted answer.
     */
    public function markAsAccepted(User $user, Reply $reply): bool
    {
        return $this->isInstructor($user, $reply->thread->scheme_id);
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
            return \Modules\Schemes\Models\Course::where('id', $schemeId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Check if user is an instructor for the scheme.
     */
    protected function isInstructor(User $user, int $schemeId): bool
    {
        // Check if user is admin (admins can also mark accepted answers)
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is instructor for this scheme
        if ($user->hasRole('instructor')) {
            return \Modules\Schemes\Models\Course::where('id', $schemeId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }
}
