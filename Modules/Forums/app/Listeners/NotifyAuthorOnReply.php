<?php

namespace Modules\Forums\Listeners;

use Modules\Forums\Events\ReplyCreated;
use Modules\Notifications\Services\NotificationService;

class NotifyAuthorOnReply
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReplyCreated $event): void
    {
        $reply = $event->reply;
        $thread = $reply->thread;

        // Determine who to notify
        if ($reply->parent_id) {
            // Reply to another reply - notify parent reply author
            $parentReply = $reply->parent;
            if ($parentReply && $parentReply->author_id != $reply->author_id) {
                $this->notificationService->send(
                    $parentReply->author_id,
                    'forum_reply_to_reply',
                    'New Reply to Your Comment',
                    "{$reply->author->name} replied to your comment",
                    [
                        'reply_id' => $reply->id,
                        'thread_id' => $thread->id,
                        'thread_title' => $thread->title,
                        'author_name' => $reply->author->name,
                    ]
                );
            }
        } else {
            // Reply to thread - notify thread author
            if ($thread->author_id != $reply->author_id) {
                $this->notificationService->send(
                    $thread->author_id,
                    'forum_reply_to_thread',
                    'New Reply to Your Thread',
                    "{$reply->author->name} replied to your thread",
                    [
                        'reply_id' => $reply->id,
                        'thread_id' => $thread->id,
                        'thread_title' => $thread->title,
                        'author_name' => $reply->author->name,
                    ]
                );
            }
        }
    }
}
