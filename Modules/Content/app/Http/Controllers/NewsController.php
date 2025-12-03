<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateNewsRequest;
use Modules\Content\Http\Requests\ScheduleContentRequest;
use Modules\Content\Http\Requests\UpdateContentRequest;
use Modules\Content\Models\News;
use Modules\Content\Services\ContentStatisticsService;

class NewsController extends Controller
{
    protected ContentServiceInterface $contentService;

    protected ContentStatisticsService $statisticsService;

    public function __construct(
        ContentServiceInterface $contentService,
        ContentStatisticsService $statisticsService
    ) {
        $this->contentService = $contentService;
        $this->statisticsService = $statisticsService;
    }

    /**
     * Display a listing of news.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->input('category_id'),
            'tag_id' => $request->input('tag_id'),
            'featured' => $request->boolean('featured'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'per_page' => $request->input('per_page', 15),
        ];

        $news = $this->contentService->getNewsFeed($filters);

        return response()->json([
            'status' => 'success',
            'data' => $news,
        ]);
    }

    /**
     * Store a newly created news article.
     */
    public function store(CreateNewsRequest $request): JsonResponse
    {
        $this->authorize('createNews', News::class);

        try {
            $news = $this->contentService->createNews(
                $request->validated(),
                auth()->user()
            );

            // Auto-publish if status is published
            if ($request->input('status') === 'published') {
                $this->contentService->publishContent($news);
            }

            // Auto-schedule if scheduled_at is provided
            if ($request->filled('scheduled_at')) {
                $this->contentService->scheduleContent(
                    $news,
                    \Carbon\Carbon::parse($request->input('scheduled_at'))
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil dibuat.',
                'data' => $news->load(['author', 'categories', 'tags']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified news article.
     */
    public function show(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)
            ->with(['author', 'categories', 'tags', 'revisions.editor'])
            ->firstOrFail();

        $this->authorize('view', $news);

        // Mark as read by current user if authenticated
        if (auth()->check()) {
            $this->contentService->markAsRead($news, auth()->user());
        }

        // Increment views
        $this->contentService->incrementViews($news);

        return response()->json([
            'status' => 'success',
            'data' => $news,
        ]);
    }

    /**
     * Update the specified news article.
     */
    public function update(UpdateContentRequest $request, string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('update', $news);

        try {
            $news = $this->contentService->updateNews(
                $news,
                $request->validated(),
                auth()->user()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil diperbarui.',
                'data' => $news->load(['author', 'categories', 'tags']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified news article.
     */
    public function destroy(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('delete', $news);

        $this->contentService->deleteContent($news, auth()->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Berita berhasil dihapus.',
        ]);
    }

    /**
     * Publish the specified news article.
     */
    public function publish(string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('publish', $news);

        $this->contentService->publishContent($news);

        return response()->json([
            'status' => 'success',
            'message' => 'Berita berhasil dipublikasikan.',
            'data' => $news->fresh(),
        ]);
    }

    /**
     * Schedule the specified news article.
     */
    public function schedule(ScheduleContentRequest $request, string $slug): JsonResponse
    {
        $news = News::where('slug', $slug)->firstOrFail();

        $this->authorize('schedule', $news);

        try {
            $this->contentService->scheduleContent(
                $news,
                \Carbon\Carbon::parse($request->input('scheduled_at'))
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Berita berhasil dijadwalkan.',
                'data' => $news->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get trending news.
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $trending = $this->statisticsService->getTrendingNews($limit);

        return response()->json([
            'status' => 'success',
            'data' => $trending,
        ]);
    }
}
