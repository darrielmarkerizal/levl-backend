<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Repositories\ThreadRepository;

class ForumDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ThreadRepository $threadRepository,
        private readonly ForumServiceInterface $forumService,
    ) {}

    public function allThreads(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        if (!$user->hasRole(['Admin', 'Superadmin', 'Instructor'])) {
            return $this->error(__('messages.forums.unauthorized_access'), [], 403);
        }

        $threadsQuery = \Spatie\QueryBuilder\QueryBuilder::for(\Modules\Forums\Models\Thread::class)
            ->allowedIncludes([
                'author',
                'course',
                'media',
                'topLevelReplies',
                'topLevelReplies.author',
                'topLevelReplies.media',
            ])
            ->allowedFilters([
                'author_id',
                \Spatie\QueryBuilder\AllowedFilter::exact('pinned', 'is_pinned'),
                \Spatie\QueryBuilder\AllowedFilter::exact('resolved', 'is_resolved'),
                \Spatie\QueryBuilder\AllowedFilter::exact('closed', 'is_closed'),
                'is_mentioned',
            ])
            ->allowedSorts(['created_at', 'updated_at', 'last_activity_at', 'views_count', 'replies_count'])
            ->defaultSort('-last_activity_at');

        if ($user->hasRole('Instructor')) {
            $instructorCourseIds = \Modules\Schemes\Models\Course::where('instructor_id', $user->id)
                ->pluck('id');
            $threadsQuery->whereIn('course_id', $instructorCourseIds);
        }

        $search = $request->input('search');
        if ($search) {
            $threadsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $threads = $threadsQuery->paginate($perPage);
        $threads->getCollection()->transform(fn($item) => new \Modules\Forums\Http\Resources\ThreadResource($item));

        return $this->paginateResponse($threads, __('messages.forums.threads_retrieved'));
    }

    public function myThreads(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $threadsQuery = \Spatie\QueryBuilder\QueryBuilder::for(\Modules\Forums\Models\Thread::class)
            ->where('author_id', $user->id)
            ->allowedIncludes([
                'author',
                'course',
                'media',
                'topLevelReplies',
                'topLevelReplies.author',
                'topLevelReplies.media',
            ])
            ->allowedFilters([
                \Spatie\QueryBuilder\AllowedFilter::exact('pinned', 'is_pinned'),
                \Spatie\QueryBuilder\AllowedFilter::exact('resolved', 'is_resolved'),
                \Spatie\QueryBuilder\AllowedFilter::exact('closed', 'is_closed'),
            ])
            ->allowedSorts(['created_at', 'updated_at', 'last_activity_at', 'views_count', 'replies_count'])
            ->defaultSort('-created_at');

        $search = $request->input('search');
        if ($search) {
            $threadsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $threads = $threadsQuery->paginate($perPage);
        $threads->getCollection()->transform(fn($item) => new \Modules\Forums\Http\Resources\ThreadResource($item));

        return $this->paginateResponse($threads, __('messages.forums.my_threads_retrieved'));
    }

    public function trendingThreads(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        if (!$user->hasRole(['Admin', 'Superadmin', 'Instructor'])) {
            return $this->error(__('messages.forums.unauthorized_access'), [], 403);
        }

        $threadsQuery = \Spatie\QueryBuilder\QueryBuilder::for(\Modules\Forums\Models\Thread::class)
            ->allowedIncludes([
                'author',
                'course',
                'media',
                'topLevelReplies',
                'topLevelReplies.author',
                'topLevelReplies.media',
            ])
            ->allowedFilters([
                'author_id',
                \Spatie\QueryBuilder\AllowedFilter::exact('pinned', 'is_pinned'),
                \Spatie\QueryBuilder\AllowedFilter::exact('resolved', 'is_resolved'),
                \Spatie\QueryBuilder\AllowedFilter::exact('closed', 'is_closed'),
            ])
            ->allowedSorts(['created_at', 'updated_at', 'views_count', 'replies_count'])
            ->defaultSort('-views_count');

        if ($user->hasRole('Instructor')) {
            $instructorCourseIds = \Modules\Schemes\Models\Course::where('instructor_id', $user->id)
                ->pluck('id');
            $threadsQuery->whereIn('course_id', $instructorCourseIds);
        }

        $search = $request->input('search');
        if ($search) {
            $threadsQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $threads = $threadsQuery->paginate($perPage);
        $threads->getCollection()->transform(fn($item) => new \Modules\Forums\Http\Resources\ThreadResource($item));

        return $this->paginateResponse($threads, __('messages.forums.trending_threads_retrieved'));
    }
}
