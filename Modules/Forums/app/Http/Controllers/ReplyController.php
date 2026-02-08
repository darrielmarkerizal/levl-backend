<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Services\ForumServiceInterface;
use Modules\Forums\Http\Requests\CreateReplyRequest;
use Modules\Forums\Http\Requests\UpdateReplyRequest;
use Modules\Forums\Http\Resources\ReplyResource;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;
use Modules\Forums\Services\ModerationService;
use Modules\Schemes\Models\Course;

class ReplyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ForumServiceInterface $forumService,
        private readonly ModerationService $moderationService
    ) {}

    public function index(Request $request, Course $course, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread) {
            return $this->notFound(__('messages.forums.thread_not_found'));
        }

        $perPage = (int) $request->input('per_page', 20);

        $repliesQuery = \Spatie\QueryBuilder\QueryBuilder::for(Reply::class)
            ->where('thread_id', $threadId)
            ->whereNull('parent_id')
            ->allowedIncludes([
                'author',
                'media',
                'children',
                'children.author',
                'children.media',
                'children.children',
                'children.children.author',
                'children.children.media',
            ])
            ->allowedSorts(['created_at', 'updated_at'])
            ->defaultSort('created_at');

        $replies = $repliesQuery->paginate($perPage);
        $replies->getCollection()->transform(fn($item) => new ReplyResource($item));

        return $this->paginateResponse($replies, __('messages.forums.replies_retrieved'));
    }

    public function store(CreateReplyRequest $request, Course $course, int $threadId): JsonResponse
    {
        $thread = Thread::find($threadId);

        if (! $thread) {
            return $this->notFound(__('messages.forums.thread_not_found'));
        }

        $this->authorize('create', [Reply::class, $thread]);

        try {
            $data = $request->validated();
            $data['attachments'] = $request->file('attachments') ?? [];

            $reply = $this->forumService->createReply(
                $thread,
                $data,
                $request->user()
            );

            $replyWithIncludes = \Spatie\QueryBuilder\QueryBuilder::for(Reply::class)
                ->where('id', $reply->id)
                ->allowedIncludes(['author', 'media', 'children'])
                ->firstOrFail();

            return $this->created(new ReplyResource($replyWithIncludes), __('messages.forums.reply_created'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    public function update(UpdateReplyRequest $request, Course $course, Reply $reply): JsonResponse
    {
        $this->authorize('update', $reply);

        $updatedReply = $this->forumService->updateReply($reply, $request->validated());

        $replyWithIncludes = \Spatie\QueryBuilder\QueryBuilder::for(Reply::class)
            ->where('id', $updatedReply->id)
            ->allowedIncludes(['author', 'media', 'children'])
            ->firstOrFail();

        return $this->success(new ReplyResource($replyWithIncludes), __('messages.forums.reply_updated'));
    }

    public function destroy(Request $request, Course $course, Reply $reply): JsonResponse
    {
        $this->authorize('delete', $reply);

        $this->forumService->deleteReply($reply, $request->user());

        return $this->success(null, __('messages.forums.reply_deleted'));
    }
}
