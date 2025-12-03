<?php

namespace Modules\Forums\Http\Controllers;

use App\Contracts\Services\ForumServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Http\Requests\CreateReplyRequest;
use Modules\Forums\Http\Requests\UpdateReplyRequest;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;

class ReplyController extends Controller
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
     * Store a newly created reply.
     */
    public function store(CreateReplyRequest $request, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        $this->authorize('create', [Reply::class, $thread]);

        try {
            $parent = null;
            if ($request->has('parent_id')) {
                $parent = Reply::find($request->input('parent_id'));
                if (! $parent || $parent->thread_id != $threadId) {
                    return ApiResponse::errorStatic('Invalid parent reply', 400);
                }
            }

            $reply = $this->forumService->createReply(
                $thread,
                $request->validated(),
                $request->user(),
                $parent
            );

            return ApiResponse::successStatic($reply, 'Reply created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified reply.
     */
    public function update(UpdateReplyRequest $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return ApiResponse::errorStatic('Reply not found', 404);
        }

        $this->authorize('update', $reply);

        try {
            $updatedReply = $this->forumService->updateReply($reply, $request->validated());

            return ApiResponse::successStatic($updatedReply, 'Reply updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified reply.
     */
    public function destroy(Request $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return ApiResponse::errorStatic('Reply not found', 404);
        }

        $this->authorize('delete', $reply);

        try {
            $this->forumService->deleteReply($reply, $request->user());

            return ApiResponse::successStatic(null, 'Reply deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Mark a reply as accepted answer.
     */
    public function accept(Request $request, int $replyId): JsonResponse
    {
        $reply = Reply::find($replyId);

        if (! $reply) {
            return ApiResponse::errorStatic('Reply not found', 404);
        }

        $this->authorize('markAsAccepted', $reply);

        try {
            $this->moderationService->markAsAcceptedAnswer($reply, $request->user());

            return ApiResponse::successStatic($reply->fresh(), 'Reply marked as accepted answer');
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }
}
