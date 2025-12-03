<?php

namespace Modules\Content\Listeners;

use Modules\Content\Events\NewsPublished;
use Modules\Content\Services\ContentNotificationService;

class NotifyUsersOnNewsPublished
{
    protected ContentNotificationService $notificationService;

    public function __construct(ContentNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(NewsPublished $event): void
    {
        $this->notificationService->notifyNewNews($event->news);
    }
}
