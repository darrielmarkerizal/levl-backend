<?php

namespace Modules\Forums\Listeners;

use Modules\Forums\Events\ThreadPinned;
use Modules\Notifications\Services\NotificationService;

class NotifyUsersOnThreadPinned
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ThreadPinned $event): void
    {
        $thread = $event->thread;
        $scheme = $thread->scheme;

        // Notify all enrolled users
        $enrollments = \Modules\Enrollments\Models\Enrollment::where('course_id', $thread->scheme_id)
            ->where('user_id', '!=', $thread->author_id)
            ->get();

        foreach ($enrollments as $enrollment) {
            $this->notificationService->send(
                $enrollment->user_id,
                'forum_thread_pinned',
                [
                    'thread_id' => $thread->id,
                    'thread_title' => $thread->title,
                    'scheme_name' => $scheme->name,
                ],
                "Important: {$thread->title}"
            );
        }
    }
}
