<?php

namespace Modules\Forums\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Repositories\ReplyRepository;
use Modules\Forums\Repositories\ThreadRepository;

class ModerationService
{
    protected ThreadRepository $threadRepository;

    protected ReplyRepository $replyRepository;

    public function __construct(
        ThreadRepository $threadRepository,
        ReplyRepository $replyRepository
    ) {
        $this->threadRepository = $threadRepository;
        $this->replyRepository = $replyRepository;
    }

    /**
     * Pin a thread.
     */
    public function pinThread(Thread $thread, User $moderator): bool
    {
        $result = $this->threadRepository->update($thread, ['is_pinned' => true]);

        $this->logModerationAction('pin_thread', $moderator, $thread);

        // Fire event for notifications
        event(new \Modules\Forums\Events\ThreadPinned($thread));

        return $result !== null;
    }

    /**
     * Unpin a thread.
     */
    public function unpinThread(Thread $thread, User $moderator): bool
    {
        $result = $this->threadRepository->update($thread, ['is_pinned' => false]);

        $this->logModerationAction('unpin_thread', $moderator, $thread);

        return $result !== null;
    }

    /**
     * Close a thread.
     */
    public function closeThread(Thread $thread, User $moderator): bool
    {
        $result = $this->threadRepository->update($thread, ['is_closed' => true]);

        $this->logModerationAction('close_thread', $moderator, $thread);

        return $result !== null;
    }

    /**
     * Open a thread.
     */
    public function openThread(Thread $thread, User $moderator): bool
    {
        $result = $this->threadRepository->update($thread, ['is_closed' => false]);

        $this->logModerationAction('open_thread', $moderator, $thread);

        return $result !== null;
    }

    /**
     * Mark a reply as accepted answer.
     */
    public function markAsAcceptedAnswer(Reply $reply, User $instructor): bool
    {
        return DB::transaction(function () use ($reply, $instructor) {
            $result = $this->replyRepository->markAsAccepted($reply);

            if ($result) {
                // Mark thread as resolved
                $thread = $reply->thread;
                $this->threadRepository->update($thread, ['is_resolved' => true]);

                $this->logModerationAction('mark_accepted_answer', $instructor, $reply);
            }

            return $result;
        });
    }

    /**
     * Unmark a reply as accepted answer.
     */
    public function unmarkAcceptedAnswer(Reply $reply, User $instructor): bool
    {
        return DB::transaction(function () use ($reply, $instructor) {
            $result = $this->replyRepository->unmarkAsAccepted($reply);

            if ($result) {
                // Mark thread as unresolved
                $thread = $reply->thread;
                $this->threadRepository->update($thread, ['is_resolved' => false]);

                $this->logModerationAction('unmark_accepted_answer', $instructor, $reply);
            }

            return $result;
        });
    }

    /**
     * Delete content with moderation logging.
     *
     * @param  Thread|Reply  $content
     */
    public function moderateDelete($content, User $moderator, string $reason): bool
    {
        $result = false;

        if ($content instanceof Thread) {
            $result = $this->threadRepository->delete($content, $moderator->id);
            $type = 'thread';
        } elseif ($content instanceof Reply) {
            $result = $this->replyRepository->delete($content, $moderator->id);
            $type = 'reply';
        }

        if ($result) {
            $this->logModerationAction("moderate_delete_{$type}", $moderator, $content, [
                'reason' => $reason,
            ]);
        }

        return $result;
    }

    /**
     * Log moderation action.
     *
     * @param  Thread|Reply  $content
     */
    protected function logModerationAction(string $action, User $moderator, $content, array $extra = []): void
    {
        $logData = [
            'action' => $action,
            'moderator_id' => $moderator->id,
            'moderator_name' => $moderator->name,
            'content_type' => get_class($content),
            'content_id' => $content->id,
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($extra)) {
            $logData = array_merge($logData, $extra);
        }

        Log::channel('daily')->info('Forum moderation action', $logData);
    }
}
