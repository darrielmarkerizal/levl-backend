<?php

namespace Modules\Forums\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forums\Contracts\Repositories\ReactionRepositoryInterface;
use Modules\Forums\Models\Reaction;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

/**
 * @tags Forum Diskusi
 */
class ReactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReactionRepositoryInterface $reactionRepository
    ) {}

    /**
     * Toggle Reaksi pada Thread
     *
     * Menambah atau menghapus reaksi pada thread. Jika reaksi sudah ada, akan dihapus. Jika belum ada, akan ditambahkan.
     *
     *
     * @summary Toggle Reaksi pada Thread
     *
     * @response 200 scenario="Success" {"success": true, "data": {"added": true}, "message": "Reaksi berhasil ditambahkan."}
     * @response 200 scenario="Success" {"success": true, "data": {"added": false}, "message": "Reaksi berhasil dihapus."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Thread tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"type": ["The selected type is invalid."]}}
     *
     * @authenticated
     */
    public function toggleThreadReaction(Request $request, int $threadId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:like,helpful,solved',
        ]);

        $thread = Thread::find($threadId);

        if (! $thread) {
            return $this->notFound(__('forums.thread_not_found'));
        }

        $added = Reaction::toggle(
            $request->user()->id,
            Thread::class,
            $threadId,
            $request->input('type')
        );

        $message = $added ? __('forums.reaction_added') : __('forums.reaction_removed');

        if ($added) {
            $reaction = $this->reactionRepository->findByUserAndReactable(
                $request->user()->id,
                Thread::class,
                $threadId
            );

            if ($reaction) {
                event(new \Modules\Forums\Events\ReactionAdded($reaction));
            }
        }

        return $this->success(['added' => $added], $message);
    }

    /**
     * Toggle Reaksi pada Balasan
     *
     * Menambah atau menghapus reaksi pada balasan. Jika reaksi sudah ada, akan dihapus. Jika belum ada, akan ditambahkan.
     *
     *
     * @summary Toggle Reaksi pada Balasan
     *
     * @response 200 scenario="Success" {"success": true, "data": {"added": true}, "message": "Reaksi berhasil ditambahkan."}
     * @response 200 scenario="Success" {"success": true, "data": {"added": false}, "message": "Reaksi berhasil dihapus."}
     * @response 404 scenario="Not Found" {"success":false,"message":"Balasan tidak ditemukan."}
     * @response 422 scenario="Validation Error" {"success": false, "message": "Validation error", "errors": {"type": ["The selected type is invalid."]}}
     *
     * @authenticated
     */
    public function toggleReplyReaction(Request $request, int $replyId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:like,helpful,solved',
        ]);

        $reply = Reply::find($replyId);

        if (! $reply) {
            return $this->notFound(__('forums.reply_not_found'));
        }

        $added = Reaction::toggle(
            $request->user()->id,
            Reply::class,
            $replyId,
            $request->input('type')
        );

        $message = $added ? __('forums.reaction_added') : __('forums.reaction_removed');

        if ($added) {
            $reaction = $this->reactionRepository->findByUserAndReactable(
                $request->user()->id,
                Reply::class,
                $replyId
            );

            if ($reaction) {
                event(new \Modules\Forums\Events\ReactionAdded($reaction));
            }
        }

        return $this->success(['added' => $added], $message);
    }
}
