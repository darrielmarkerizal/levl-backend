<?php

namespace Modules\Forums\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ReplyRepository
{
    /**
     * Get replies for a thread with hierarchy.
     */
    public function getRepliesForThread(int $threadId): Collection
    {
        return Reply::where('thread_id', $threadId)
            ->with(['author', 'children.author', 'children.children.author'])
            ->withCount('reactions')
            ->orderBy('is_accepted_answer', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get top-level replies for a thread (no parent).
     */
    public function getTopLevelReplies(int $threadId): Collection
    {
        return Reply::where('thread_id', $threadId)
            ->topLevel()
            ->with(['author', 'children.author'])
            ->withCount('reactions')
            ->orderBy('is_accepted_answer', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get nested replies for a parent reply.
     */
    public function getNestedReplies(int $parentId): Collection
    {
        return Reply::where('parent_id', $parentId)
            ->with(['author', 'children.author'])
            ->withCount('reactions')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Create a new reply.
     */
    public function create(array $data): Reply
    {
        return Reply::create($data);
    }

    /**
     * Update a reply.
     */
    public function update(Reply $reply, array $data): Reply
    {
        $reply->update($data);

        return $reply->fresh();
    }

    /**
     * Delete a reply (soft delete).
     */
    public function delete(Reply $reply, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $reply->deleted_by = $deletedBy;
            $reply->save();
        }

        return $reply->delete();
    }

    /**
     * Find a reply by ID with relationships.
     */
    public function findWithRelations(int $replyId): ?Reply
    {
        return Reply::with(['author', 'thread', 'parent', 'children'])
            ->withCount('reactions')
            ->find($replyId);
    }

    /**
     * Get the accepted answer for a thread.
     */
    public function getAcceptedAnswer(int $threadId): ?Reply
    {
        return Reply::where('thread_id', $threadId)
            ->accepted()
            ->with(['author'])
            ->first();
    }

    /**
     * Mark a reply as accepted answer and unmark others.
     */
    public function markAsAccepted(Reply $reply): bool
    {
        // Unmark any existing accepted answer for this thread
        Reply::where('thread_id', $reply->thread_id)
            ->where('is_accepted_answer', true)
            ->update(['is_accepted_answer' => false]);

        // Mark this reply as accepted
        $reply->is_accepted_answer = true;

        return $reply->save();
    }

    /**
     * Unmark a reply as accepted answer.
     */
    public function unmarkAsAccepted(Reply $reply): bool
    {
        $reply->is_accepted_answer = false;

        return $reply->save();
    }
}
