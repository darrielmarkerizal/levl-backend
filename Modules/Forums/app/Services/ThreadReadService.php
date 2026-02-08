<?php

declare(strict_types=1);

namespace Modules\Forums\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Forums\Http\Resources\ThreadResource;
use Modules\Forums\Models\Thread;
use Spatie\QueryBuilder\QueryBuilder;

class ThreadReadService
{
    public function paginateCourseThreads(int $courseId, ?string $search, int $perPage): LengthAwarePaginator
    {
        $threadsQuery = QueryBuilder::for(Thread::class)
            ->where('course_id', $courseId)
            ->allowedIncludes([
                'author',
                'course',
                'media',
                'replies',
                'replies.author',
                'replies.media',
            ])
            ->allowedSorts(['created_at', 'updated_at', 'views_count', 'replies_count'])
            ->defaultSort('-created_at');

        if ($search) {
            $threadsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $threads = $threadsQuery->paginate($perPage);
        $threads->getCollection()->transform(fn ($item) => new ThreadResource($item));

        return $threads;
    }

    public function getThreadDetail(int $threadId): Thread
    {
        return QueryBuilder::for(Thread::class)
            ->where('id', $threadId)
            ->allowedIncludes([
                'author',
                'course',
                'media',
                'topLevelReplies',
                'topLevelReplies.author',
                'topLevelReplies.media',
                'topLevelReplies.children',
                'topLevelReplies.children.author',
                'topLevelReplies.children.media',
                'topLevelReplies.children.children',
                'topLevelReplies.children.children.author',
                'topLevelReplies.children.children.media',
            ])
            ->firstOrFail();
    }

    public function getThreadSummary(int $threadId): Thread
    {
        return QueryBuilder::for(Thread::class)
            ->where('id', $threadId)
            ->allowedIncludes(['author', 'course', 'media'])
            ->firstOrFail();
    }
}
