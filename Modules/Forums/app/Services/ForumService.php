<?php

namespace Modules\Forums\Services;

use App\Contracts\Services\ForumServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Repositories\ReplyRepository;
use Modules\Forums\Repositories\ThreadRepository;

class ForumService implements ForumServiceInterface
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
     * Create a new thread.
     *
     * @throws \Exception
     */
    public function createThread(array $data, User $user): Thread
    {
        return DB::transaction(function () use ($data, $user) {
            $threadData = [
                'scheme_id' => $data['scheme_id'],
                'author_id' => $user->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'last_activity_at' => now(),
            ];

            $thread = $this->threadRepository->create($threadData);

            // Fire event for notifications
            event(new \Modules\Forums\Events\ThreadCreated($thread));

            return $thread;
        });
    }

    /**
     * Update a thread.
     */
    public function updateThread(Thread $thread, array $data): Thread
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        if (! empty($updateData)) {
            $updateData['edited_at'] = now();
        }

        return $this->threadRepository->update($thread, $updateData);
    }

    /**
     * Delete a thread (soft delete).
     */
    public function deleteThread(Thread $thread, User $user): bool
    {
        return $this->threadRepository->delete($thread, $user->id);
    }

    /**
     * Get threads for a scheme with sorting and filters.
     */
    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        return $this->threadRepository->getThreadsForScheme($schemeId, $filters);
    }

    /**
     * Search threads by query.
     */
    public function searchThreads(string $query, int $schemeId): LengthAwarePaginator
    {
        return $this->threadRepository->searchThreads($query, $schemeId);
    }

    /**
     * Get a thread with all details.
     */
    public function getThreadDetail(int $threadId): ?Thread
    {
        $thread = $this->threadRepository->findWithRelations($threadId);

        if ($thread) {
            $thread->incrementViews();
        }

        return $thread;
    }

    /**
     * Create a reply to a thread or another reply.
     *
     * @throws \Exception
     */
    public function createReply(Thread $thread, array $data, User $user, ?Reply $parent = null): Reply
    {
        // Check if thread is closed
        if ($thread->isClosed()) {
            throw new \Exception('Cannot reply to a closed thread.');
        }

        // Check depth limit if replying to another reply
        if ($parent && ! $parent->canHaveChildren()) {
            throw new \Exception('Maximum reply depth exceeded.');
        }

        return DB::transaction(function () use ($thread, $data, $user, $parent) {
            $replyData = [
                'thread_id' => $thread->id,
                'parent_id' => $parent?->id,
                'author_id' => $user->id,
                'content' => $data['content'],
            ];

            $reply = $this->replyRepository->create($replyData);

            // Update thread's reply count and last activity
            $thread->increment('replies_count');
            $thread->updateLastActivity();

            // Fire event for notifications
            event(new \Modules\Forums\Events\ReplyCreated($reply));

            return $reply;
        });
    }

    /**
     * Update a reply.
     */
    public function updateReply(Reply $reply, array $data): Reply
    {
        $updateData = [];

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
            $updateData['edited_at'] = now();
        }

        return $this->replyRepository->update($reply, $updateData);
    }

    /**
     * Delete a reply (soft delete).
     */
    public function deleteReply(Reply $reply, User $user): bool
    {
        return DB::transaction(function () use ($reply, $user) {
            $thread = $reply->thread;

            $result = $this->replyRepository->delete($reply, $user->id);

            if ($result) {
                // Decrement thread's reply count
                $thread->decrement('replies_count');
            }

            return $result;
        });
    }
}
