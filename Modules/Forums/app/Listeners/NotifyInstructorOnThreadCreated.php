<?php

namespace Modules\Forums\Listeners;

use Modules\Forums\Events\ThreadCreated;
use Modules\Notifications\Services\NotificationService;

class NotifyInstructorOnThreadCreated
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ThreadCreated $event): void
    {
        $thread = $event->thread;
        $scheme = $thread->scheme;

        // Get instructor for this scheme
        if ($scheme && $scheme->instructor_id) {
            $this->notificationService->send(
                $scheme->instructor_id,
                'forum_thread_created',
                [
                    'thread_id' => $thread->id,
                    'thread_title' => $thread->title,
                    'author_name' => $thread->author->name,
                    'scheme_name' => $scheme->name,
                ],
                "New thread created: {$thread->title}"
            );
        }

        // If author is instructor, notify all enrolled students
        if ($thread->author->hasRole('instructor')) {
            $enrollments = \Modules\Enrollments\Models\Enrollment::where('course_id', $thread->scheme_id)
                ->where('user_id', '!=', $thread->author_id)
                ->get();

            foreach ($enrollments as $enrollment) {
                $this->notificationService->send(
                    $enrollment->user_id,
                    'forum_instructor_thread',
                    [
                        'thread_id' => $thread->id,
                        'thread_title' => $thread->title,
                        'instructor_name' => $thread->author->name,
                        'scheme_name' => $scheme->name,
                    ],
                    "Instructor posted: {$thread->title}"
                );
            }
        }
    }
}
