<?php

namespace Modules\Notifications\Listeners;

use Modules\Notifications\Services\NotificationService;
use Modules\Schemes\Events\CourseCompleted;

class NotifyUserOnCourseCompleted
{
    public function __construct(private NotificationService $notificationService) {}

    public function handle(CourseCompleted $event): void
    {
        $course = $event->course;
        $enrollment = $event->enrollment;

        if (! $course || ! $enrollment || ! $enrollment->user) {
            return;
        }

        $user = $enrollment->user;

        // Create in-app notification
        $this->notificationService->create([
            'user_id' => $user->id,
            'type' => 'course_completed',
            'title' => 'Selamat! Course Selesai',
            'message' => sprintf(
                'Anda telah menyelesaikan course "%s". Selamat atas pencapaian Anda!',
                $course->title
            ),
            'data' => [
                'course_id' => $course->id,
                'course_slug' => $course->slug,
                'enrollment_id' => $enrollment->id,
                'completed_at' => $enrollment->completed_at?->toIso8601String(),
            ],
            'action_url' => '/courses/'.$course->slug,
        ]);
    }
}
