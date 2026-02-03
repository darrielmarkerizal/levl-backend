<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Http\Requests\CreateThreadRequest;
use Modules\Forums\Http\Requests\UpdateThreadRequest;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;

 
class ThreadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ForumServiceInterface $forumService,
        private readonly ModerationService $moderationService
    ) {}

     
    public function index(Request $request, int $schemeId): JsonResponse
    {
        $filters = [
            'per_page' => $request->input('per_page', 20),
        ];
        
        $search = $request->input('search');

        $threads = $this->forumService->getThreadsForScheme($schemeId, $filters, $search);

        return $this->paginateResponse($threads, __('forums.threads_retrieved'));
    }

     
    public function store(CreateThreadRequest $request, int $schemeId): JsonResponse
    {
        $data = $request->validated();
        $data['scheme_id'] = $schemeId;

        $thread = $this->forumService->createThread($data, auth()->user());

        return $this->created($thread, __('forums.thread_created'));
    }

     
    public function show(int $schemeId, Thread $thread): JsonResponse
    {
        if ($thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        
        $thread = $this->forumService->getThreadDetail($thread->id);

        return $this->success($thread, __('forums.thread_retrieved'));
    }

     
    public function update(UpdateThreadRequest $request, int $schemeId, Thread $thread): JsonResponse
    {
        if ($thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('update', $thread);

        $updatedThread = $this->forumService->updateThread($thread, $request->validated());

        return $this->success($updatedThread, __('forums.thread_updated'));
    }

     
    public function destroy(Request $request, int $schemeId, Thread $thread): JsonResponse
    {
        if ($thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('delete', $thread);

        $this->forumService->deleteThread($thread, $request->user());

        return $this->success(null, __('forums.thread_deleted'));
    }

     
    public function pin(Request $request, int $schemeId, Thread $thread): JsonResponse
    {
        if ($thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('pin', $thread);

        $pinnedThread = $this->moderationService->pinThread($thread, $request->user());

        return $this->success($pinnedThread, __('forums.thread_pinned'));
    }

     
    public function close(Request $request, int $schemeId, Thread $thread): JsonResponse
    {
        if ($thread->scheme_id != $schemeId) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $this->authorize('close', $thread);

        $closedThread = $this->moderationService->closeThread($thread, $request->user());

        return $this->success($closedThread, __('forums.thread_closed'));
    }

     

}
