<?php

namespace Modules\Content\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Content\Events\ContentApproved;

class NotifyAuthorOnContentApproved
{
    /**
     * Handle the event.
     */
    public function handle(ContentApproved $event): void
    {
        $author = $event->content->author;

        if (! $author) {
            Log::warning('Content has no author to notify for approval');

            return;
        }

        $contentType = class_basename($event->content);
        $contentTitle = $event->content->title ?? 'Untitled';
        $approverName = $event->user->name;

        // Send notification to author
        // In a real implementation, this would use the Notifications module
        // Notification::send($author, new ContentApprovedNotification($event->content, $event->user));

        Log::info("Notifying author {$author->name} that their {$contentType} '{$contentTitle}' was approved by {$approverName}");
    }
}
