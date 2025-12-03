<?php

namespace Modules\Content\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Content\Contracts\ContentWorkflowServiceInterface;
use Modules\Content\Exceptions\InvalidTransitionException;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;

class ContentApprovalController extends Controller
{
    public function __construct(
        private ContentWorkflowServiceInterface $workflowService
    ) {}

    /**
     * Submit content for review.
     */
    public function submit(Request $request, string $type, int $id): JsonResponse
    {
        $content = $this->findContent($type, $id);

        if (! $content) {
            return ApiResponse::error('Content not found', 404);
        }

        try {
            $this->workflowService->submitForReview($content, Auth::user());

            return ApiResponse::success([
                'message' => 'Content submitted for review successfully',
                'content' => $content->fresh(),
            ]);
        } catch (InvalidTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Approve content.
     */
    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $content = $this->findContent($type, $id);

        if (! $content) {
            return ApiResponse::error('Content not found', 404);
        }

        try {
            $this->workflowService->approve(
                $content,
                Auth::user(),
                $request->input('note')
            );

            return ApiResponse::success([
                'message' => 'Content approved successfully',
                'content' => $content->fresh(),
            ]);
        } catch (InvalidTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Reject content.
     */
    public function reject(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $content = $this->findContent($type, $id);

        if (! $content) {
            return ApiResponse::error('Content not found', 404);
        }

        try {
            $this->workflowService->reject(
                $content,
                Auth::user(),
                $request->input('reason')
            );

            return ApiResponse::success([
                'message' => 'Content rejected',
                'content' => $content->fresh(),
            ]);
        } catch (InvalidTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Get content pending review.
     */
    public function pendingReview(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all');

        $pendingContent = [];

        if ($type === 'all' || $type === 'news') {
            $news = News::whereIn('status', ['submitted', 'in_review'])
                ->with('author')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'news',
                        'title' => $item->title,
                        'status' => $item->status,
                        'author' => $item->author->name,
                        'created_at' => $item->created_at,
                    ];
                });

            $pendingContent = array_merge($pendingContent, $news->toArray());
        }

        if ($type === 'all' || $type === 'announcement') {
            $announcements = Announcement::whereIn('status', ['submitted', 'in_review'])
                ->with('author')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => 'announcement',
                        'title' => $item->title,
                        'status' => $item->status,
                        'author' => $item->author->name,
                        'created_at' => $item->created_at,
                    ];
                });

            $pendingContent = array_merge($pendingContent, $announcements->toArray());
        }

        // Sort by created_at descending
        usort($pendingContent, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return ApiResponse::success([
            'pending_content' => $pendingContent,
            'count' => count($pendingContent),
        ]);
    }

    /**
     * Find content by type and ID.
     */
    private function findContent(string $type, int $id)
    {
        return match ($type) {
            'news' => News::find($id),
            'announcement' => Announcement::find($id),
            default => null,
        };
    }
}
