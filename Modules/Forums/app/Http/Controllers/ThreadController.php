<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Http\Requests\CreateThreadRequest;
use Modules\Forums\Http\Requests\UpdateThreadRequest;
use Modules\Forums\Http\Resources\ThreadResource;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;
use Modules\Forums\Services\ThreadReadService;
use Modules\Schemes\Models\Course;

class ThreadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ForumServiceInterface $forumService,
        private readonly ModerationService $moderationService,
        private readonly ThreadReadService $threadReadService
    ) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        $threads = $this->threadReadService->paginateCourseThreads(
            $course->id,
            $request->input('search'),
            (int) $request->input('per_page', 20)
        );

        return $this->paginateResponse($threads, __('messages.forums.threads_retrieved'));
    }

    public function store(CreateThreadRequest $request, Course $course): JsonResponse
    {
        $data = $request->validated();
        $data['attachments'] = $request->file('attachments') ?? [];

        $thread = $this->forumService->createThread($data, $request->user(), $course->id);

        $threadWithIncludes = $this->threadReadService->getThreadSummary($thread->id);

        return $this->created(new ThreadResource($threadWithIncludes), __('messages.forums.thread_created'));
    }

    public function show(Request $request, Course $course, Thread $thread): JsonResponse
    {
        $threadDetail = $this->threadReadService->getThreadDetail($thread->id);

        return $this->success(new ThreadResource($threadDetail), __('messages.forums.thread_retrieved'));
    }

    public function update(UpdateThreadRequest $request, Course $course, Thread $thread): JsonResponse
    {
        $this->authorize('update', $thread);

        $updatedThread = $this->forumService->updateThread($thread, $request->validated());

        $threadWithIncludes = $this->threadReadService->getThreadSummary($updatedThread->id);

        return $this->success(new ThreadResource($threadWithIncludes), __('messages.forums.thread_updated'));
    }

    public function destroy(Request $request, Course $course, Thread $thread): JsonResponse
    {
        $this->authorize('delete', $thread);

        $this->forumService->deleteThread($thread, $request->user());

        return $this->success(null, __('messages.forums.thread_deleted'));
    }

    public function pin(Request $request, Course $course, Thread $thread): JsonResponse
    {
        $this->authorize('pin', $thread);

        $pinnedThread = $this->moderationService->pinThread($thread, $request->user());

        $threadWithIncludes = $this->threadReadService->getThreadSummary($pinnedThread->id);

        return $this->success(new ThreadResource($threadWithIncludes), __('messages.forums.thread_pinned'));
    }

    public function unpin(Request $request, Course $course, Thread $thread): JsonResponse
    {
        $this->authorize('unpin', $thread);

        $unpinnedThread = $this->moderationService->unpinThread($thread, $request->user());

        $threadWithIncludes = $this->threadReadService->getThreadSummary($unpinnedThread->id);

        return $this->success(new ThreadResource($threadWithIncludes), __('messages.forums.thread_unpinned'));
    }

    public function close(Request $request, Course $course, Thread $thread): JsonResponse
    {
        $this->authorize('close', $thread);

        $closedThread = $this->moderationService->closeThread($thread, $request->user());

        $threadWithIncludes = $this->threadReadService->getThreadSummary($closedThread->id);

        return $this->success(new ThreadResource($threadWithIncludes), __('messages.forums.thread_closed'));
    }
}
