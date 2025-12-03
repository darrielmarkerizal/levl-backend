<?php

namespace Modules\Forums\Listeners;

use Modules\Forums\Events\ReactionAdded;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Notifications\Services\NotificationService;

class NotifyAuthorOnReaction
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReactionAdded $event): void
    {
        $reaction = $event->reaction;
        $reactable = $reaction->reactable;

        // Don't notify if user reacted to their own content
        if ($reactable->author_id == $reaction->user_id) {
            return;
        }

        $reactionType = ucfirst($reaction->type);
        $userName = $reaction->user->name;

        if ($reactable instanceof Thread) {
            $this->notificationService->send(
                $reactable->author_id,
                'forum_reaction_thread',
                [
                    'thread_id' => $reactable->id,
                    'thread_title' => $reactable->title,
                    'user_name' => $userName,
                    'reaction_type' => $reactionType,
                ],
                "{$userName} reacted {$reactionType} to your thread"
            );
        } elseif ($reactable instanceof Reply) {
            $this->notificationService->send(
                $reactable->author_id,
                'forum_reaction_reply',
                [
                    'reply_id' => $reactable->id,
                    'thread_id' => $reactable->thread_id,
                    'thread_title' => $reactable->thread->title,
                    'user_name' => $userName,
                    'reaction_type' => $reactionType,
                ],
                "{$userName} reacted {$reactionType} to your reply"
            );
        }
    }
}
