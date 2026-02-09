<?php

declare(strict_types=1);

namespace Modules\Forums\Policies;

use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Thread;

class ThreadPolicy
{
    public function view(User $user, Thread $thread): bool
    {
        if ($user->hasRole(['Admin', 'Superadmin'])) {
            return true;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $thread->course_id)
            ->exists();
    }

    public function create(User $user, int $courseId): bool
    {
        if ($user->hasRole(['Admin', 'Superadmin'])) {
            return true;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();
    }

    public function update(User $user, Thread $thread): bool
    {
        if ($user->hasRole(['Admin', 'Superadmin'])) {
            return true;
        }

        return $user->id === $thread->author_id;
    }

    public function delete(User $user, Thread $thread): bool
    {
        return $user->id === $thread->author_id || $this->isModerator($user, $thread->course_id);
    }

    public function pin(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->course_id);
    }

    public function unpin(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->course_id);
    }

    public function close(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->course_id);
    }

    public function open(User $user, Thread $thread): bool
    {
        return $this->isModerator($user, $thread->course_id);
    }

    public function resolve(User $user, Thread $thread): bool
    {
        return $user->id === $thread->author_id || $this->isModerator($user, $thread->course_id);
    }

    public function unresolve(User $user, Thread $thread): bool
    {
        return $user->id === $thread->author_id || $this->isModerator($user, $thread->course_id);
    }

    protected function isModerator(User $user, int $courseId): bool
    {
        if ($user->hasRole(['Admin', 'Superadmin'])) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            return \Modules\Schemes\Models\Course::where('id', $courseId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }
}
