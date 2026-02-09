<?php

namespace Modules\Forums\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Forums\Contracts\Services\ModerationServiceInterface;
use Modules\Forums\Events\ThreadClosed;
use Modules\Forums\Events\ThreadOpened;
use Modules\Forums\Events\ThreadResolved;
use Modules\Forums\Events\ThreadUnresolved;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Repositories\ReplyRepository;
use Modules\Forums\Repositories\ThreadRepository;

class ModerationService implements ModerationServiceInterface
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

     
    public function pinThread(Thread $thread, User $moderator): Thread
    {
        $this->threadRepository->update($thread, ['is_pinned' => true]);

        $this->logModerationAction('pin_thread', $moderator, $thread);

        
        event(new \Modules\Forums\Events\ThreadPinned($thread));

        return $thread->fresh();
    }

     
    public function unpinThread(Thread $thread, User $moderator): Thread
    {
        $this->threadRepository->update($thread, ['is_pinned' => false]);

        $this->logModerationAction('unpin_thread', $moderator, $thread);

        return $thread->fresh();
    }

     
    public function closeThread(Thread $thread, User $actor): Thread
    {
        $thread->is_closed = true;
        $thread->save();

        event(new ThreadClosed($thread, $actor));

        return $thread->fresh();
    }

    public function openThread(Thread $thread, User $actor): Thread
    {
        $thread->is_closed = false;
        $thread->save();

        event(new ThreadOpened($thread, $actor));

        return $thread->fresh();
    }

    public function resolveThread(Thread $thread, User $actor): Thread
    {
        $thread->is_resolved = true;
        $thread->save();

        event(new ThreadResolved($thread, $actor));

        return $thread->fresh();
    }

    public function unresolveThread(Thread $thread, User $actor): Thread
    {
        $thread->is_resolved = false;
        $thread->save();

        event(new ThreadUnresolved($thread, $actor));

        return $thread->fresh();
    }
     
    public function reopenThread(Thread $thread, User $moderator): Thread
    {
        $this->threadRepository->update($thread, ['is_closed' => false]);

        $this->logModerationAction('open_thread', $moderator, $thread);

        return $thread->fresh();
    }

     
    public function markAsAcceptedAnswer(Reply $reply, User $user): Reply
    {
        return DB::transaction(function () use ($reply, $user) {
            $this->replyRepository->markAsAccepted($reply);

            
            $thread = $reply->thread;
            $this->threadRepository->update($thread, ['is_resolved' => true]);

            $this->logModerationAction('mark_accepted_answer', $user, $reply);

            return $reply->fresh();
        });
    }

     
    public function unmarkAsAcceptedAnswer(Reply $reply, User $user): Reply
    {
        return DB::transaction(function () use ($reply, $user) {
            $this->replyRepository->unmarkAsAccepted($reply);

            
            $thread = $reply->thread;
            $this->threadRepository->update($thread, ['is_resolved' => false]);

            $this->logModerationAction('unmark_accepted_answer', $user, $reply);

            return $reply->fresh();
        });
    }

     
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
