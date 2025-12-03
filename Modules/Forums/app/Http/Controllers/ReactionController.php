<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ReactionController extends Controller
{
    /**
     * Toggle a reaction on a thread.
     */
    public function toggleThreadReaction(Request $request, int $threadId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:like,helpful,solved',
        ]);

        $thread = Thread::find($threadId);

        if (! $thread) {
            return ApiResponse::errorStatic('Thread not found', 404);
        }

        try {
            $added = Reaction::toggle(
                $request->user()->id,
                Thread::class,
                $threadId,
                $request->input('type')
            );

            $message = $added ? 'Reaction added successfully' : 'Reaction removed successfully';

            // Fire event if reaction was added
            if ($added) {
                $reaction = Reaction::where([
                    'user_id' => $request->user()->id,
                    'reactable_type' => Thread::class,
                    'reactable_id' => $threadId,
                    'type' => $request->input('type'),
                ])->first();

                if ($reaction) {
                    event(new \Modules\Forums\Events\ReactionAdded($reaction));
                }
            }

            return ApiResponse::successStatic(['added' => $added], $message);
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }

    /**
     * Toggle a reaction on a reply.
     */
    public function toggleReplyReaction(Request $request, int $replyId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:like,helpful,solved',
        ]);

        $reply = Reply::find($replyId);

        if (! $reply) {
            return ApiResponse::errorStatic('Reply not found', 404);
        }

        try {
            $added = Reaction::toggle(
                $request->user()->id,
                Reply::class,
                $replyId,
                $request->input('type')
            );

            $message = $added ? 'Reaction added successfully' : 'Reaction removed successfully';

            // Fire event if reaction was added
            if ($added) {
                $reaction = Reaction::where([
                    'user_id' => $request->user()->id,
                    'reactable_type' => Reply::class,
                    'reactable_id' => $replyId,
                    'type' => $request->input('type'),
                ])->first();

                if ($reaction) {
                    event(new \Modules\Forums\Events\ReactionAdded($reaction));
                }
            }

            return ApiResponse::successStatic(['added' => $added], $message);
        } catch (\Exception $e) {
            return ApiResponse::errorStatic($e->getMessage(), 500);
        }
    }
}
