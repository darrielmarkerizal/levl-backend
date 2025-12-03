<?php

namespace Modules\Forums\Http\Controllers;

use App\Contracts\Services\ForumServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Http\Requests\CreateThreadRequest;
use Modules\Forums\Http\Requests\UpdateThreadRequest;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;

class ThreadController extends Controller
{
    protected ForumServiceInterface $forumService;

    protected ModerationService $moderationService;

    public function __construct(
        ForumServiceInterface $forumService,
        ModerationService $moderationService
    ) {
        $this->forumService = $forumService;
        $this->moderationService = $moderationService;
    }

    /**
     * Display a listing of threads for a scheme.
     */
    public function index(Request $request, int $schemeId): JsonResponse
    {
        $filters = [
            'pinned' => $request->boolean('pinned'),
            'resolved' => $request->boolean('resolved'),
            'closed' => $request->has('closed') ? $request->boolean('closed') : null,
            'per_page' => $request->input('per_page', 20),
        ];

        $threads = $this->forumService->getThreadsForScheme($schemeId, $filters);

        return ApiResponse::successStatic($threads, 'Threads retrieved successfully');
    }

    /**
     * Store a newly created thread.
     */
    public function store(CreateThreadRequest $request, int $schemeId): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), ['scheme_id' => $schemeId]);
            $thread = $this->forumService->createThread($data, $request->user());

            return ApiResponse::successStatic($thread, 'Thread created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified thread.
     */
    public function show(int $schemeId, int $threadId): JsonResponse
    {
        $thread = $this->forumService->getThreadDetail($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        return ApiResponse::successStatic($thread, 'Thread retrieved successfully');
    }

    /**
     * Update the specified thread.
     */
    public function update(UpdateThreadRequest $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        $this->authorize('update', $thread);

        try {
            $updatedThread = $this->forumService->updateThread($thread, $request->validated());

            return ApiResponse::successStatic($updatedThread, 'Thread updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified thread.
     */
    public function destroy(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        $this->authorize('delete', $thread);

        try {
            $this->forumService->deleteThread($thread, $request->user());

            return ApiResponse::successStatic(null, 'Thread deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Pin a thread.
     */
    public function pin(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        $this->authorize('pin', $thread);

        try {
            $this->moderationService->pinThread($thread, $request->user());

            return ApiResponse::successStatic($thread->fresh(), 'Thread pinned successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Close a thread.
     */
    public function close(Request $request, int $schemeId, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread || $thread->scheme_id != $schemeId) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        $this->authorize('close', $thread);

        try {
            $this->moderationService->closeThread($thread, $request->user());

            return ApiResponse::successStatic($thread->fresh(), 'Thread closed successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Search threads.
     */
    public function search(Request $request, int $schemeId): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return ApiResponse::errorStatic('Search query is required', 400);
        }

        $threads = $this->forumService->searchThreads($query, $schemeId);

        return ApiResponse::successStatic($threads, 'Search results retrieved successfully');
    }
}
