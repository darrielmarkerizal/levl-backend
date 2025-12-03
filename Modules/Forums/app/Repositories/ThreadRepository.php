<?php

namespace Modules\Forums\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Forums\Models\Thread;

class ThreadRepository
{
    /**
     * Get threads for a specific scheme with filters.
     */
    public function getThreadsForScheme(int $schemeId, array $filters = []): LengthAwarePaginator
    {
        $query = Thread::forScheme($schemeId)
            ->with(['author', 'replies'])
            ->withCount('replies');

        // Apply filters
        if (isset($filters['pinned']) && $filters['pinned']) {
            $query->pinned();
        }

        if (isset($filters['resolved']) && $filters['resolved']) {
            $query->resolved();
        }

        if (isset($filters['closed'])) {
            if ($filters['closed']) {
                $query->closed();
            } else {
                $query->open();
            }
        }

        // Sort by pinned first, then by last activity
        $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc');

        // Paginate with 20 items per page
        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Search threads by title and content.
     */
    public function searchThreads(string $searchQuery, int $schemeId, int $perPage = 20): LengthAwarePaginator
    {
        return Thread::forScheme($schemeId)
            ->whereRaw('MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery])
            ->with(['author', 'replies'])
            ->withCount('replies')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a thread by ID with relationships.
     */
    public function findWithRelations(int $threadId): ?Thread
    {
        return Thread::with(['author', 'scheme', 'replies.author', 'replies.children'])
            ->withCount('replies')
            ->find($threadId);
    }

    /**
     * Create a new thread.
     */
    public function create(array $data): Thread
    {
        $thread = Thread::create($data);
        $thread->updateLastActivity();

        return $thread;
    }

    /**
     * Update a thread.
     */
    public function update(Thread $thread, array $data): Thread
    {
        $thread->update($data);

        return $thread->fresh();
    }

    /**
     * Delete a thread (soft delete).
     */
    public function delete(Thread $thread, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $thread->deleted_by = $deletedBy;
            $thread->save();
        }

        return $thread->delete();
    }

    /**
     * Get pinned threads for a scheme.
     */
    public function getPinnedThreads(int $schemeId): Collection
    {
        return Thread::forScheme($schemeId)
            ->pinned()
            ->with(['author'])
            ->withCount('replies')
            ->orderBy('last_activity_at', 'desc')
            ->get();
    }
}
