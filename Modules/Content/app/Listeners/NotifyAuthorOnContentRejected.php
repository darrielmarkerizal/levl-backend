<?php

namespace Modules\Content\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Content\Events\ContentRejected;
use Modules\Content\Models\ContentWorkflowHistory;

class NotifyAuthorOnContentRejected
{
    /**
     * Handle the event.
     */
    public function handle(ContentRejected $event): void
    {
        $author = $event->content->author;

        if (! $author) {
            Log::warning('Content has no author to notify for rejection');

            return;
        }

        $contentType = class_basename($event->content);
        $contentTitle = $event->content->title ?? 'Untitled';
        $reviewerName = $event->user->name;

        // Get rejection reason from workflow history
        $reason = ContentWorkflowHistory::where('content_type', get_class($event->content))
            ->where('content_id', $event->content->id)
            ->where('to_state', 'rejected')
            ->latest()
            ->value('note');

        // Send notification to author with rejection reason
        // In a real implementation, this would use the Notifications module
        // Notification::send($author, new ContentRejectedNotification($event->content, $event->user, $reason));

        Log::info("Notifying author {$author->name} that their {$contentType} '{$contentTitle}' was rejected by {$reviewerName}. Reason: {$reason}");
    }
}
