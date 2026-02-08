<?php

declare(strict_types=1);

namespace Modules\Forums\Listeners;

use Carbon\Carbon;
use Modules\Forums\Events\ReplyCreated;
use Modules\Forums\Events\ThreadCreated;
use Modules\Forums\Repositories\ForumStatisticsRepository;

class UpdateForumStatistics
{
    public function __construct(
        protected ForumStatisticsRepository $statisticsRepository
    ) {}

    public function handle($event): void
    {
        $now = Carbon::now();
        $periodStart = $now->copy()->startOfMonth();
        $periodEnd = $now->copy()->endOfMonth();

        if ($event instanceof ThreadCreated) {
            $thread = $event->thread;
            $courseId = $thread->course_id;

            if (! $courseId) {
                return;
            }

            $this->statisticsRepository->updateSchemeStatistics(
                $courseId,
                $periodStart,
                $periodEnd
            );

            $this->statisticsRepository->updateUserStatistics(
                $courseId,
                $thread->author_id,
                $periodStart,
                $periodEnd
            );

            return;
        }

        if ($event instanceof ReplyCreated) {
            $reply = $event->reply;
            $thread = $reply->thread;
            $courseId = $thread?->course_id;

            if (! $courseId) {
                return;
            }

            $this->statisticsRepository->updateSchemeStatistics(
                $courseId,
                $periodStart,
                $periodEnd
            );

            $this->statisticsRepository->updateUserStatistics(
                $courseId,
                $reply->author_id,
                $periodStart,
                $periodEnd
            );
        }
    }
}
