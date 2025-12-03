<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateAnnouncementRequest;
use Modules\Content\Http\Requests\ScheduleContentRequest;
use Modules\Content\Http\Requests\UpdateContentRequest;
use Modules\Content\Models\Announcement;

class AnnouncementController extends Controller
{
    protected ContentServiceInterface $contentService;

    public function __construct(ContentServiceInterface $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * Display a listing of announcements.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $filters = [
            'course_id' => $request->input('course_id'),
            'priority' => $request->input('priority'),
            'unread' => $request->boolean('unread'),
            'per_page' => $request->input('per_page', 15),
        ];

        $announcements = $this->contentService->getAnnouncementsForUser($user, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $announcements,
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(CreateAnnouncementRequest $request): JsonResponse
    {
        $this->authorize('createAnnouncement', Announcement::class);

        try {
            $announcement = $this->contentService->createAnnouncement(
                $request->validated(),
                auth()->user()
            );

            // Auto-publish if status is published
            if ($request->input('status') === 'published') {
                $this->contentService->publishContent($announcement);
            }

            // Auto-schedule if scheduled_at is provided
            if ($request->filled('scheduled_at')) {
                $this->contentService->scheduleContent(
                    $announcement,
                    \Carbon\Carbon::parse($request->input('scheduled_at'))
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dibuat.',
                'data' => $announcement->load(['author', 'course']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified announcement.
     */
    public function show(int $id): JsonResponse
    {
        $announcement = Announcement::with(['author', 'course', 'revisions.editor'])
            ->findOrFail($id);

        $this->authorize('view', $announcement);

        // Mark as read by current user
        $this->contentService->markAsRead($announcement, auth()->user());

        // Increment views
        $this->contentService->incrementViews($announcement);

        return response()->json([
            'status' => 'success',
            'data' => $announcement,
        ]);
    }

    /**
     * Update the specified announcement.
     */
    public function update(UpdateContentRequest $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('update', $announcement);

        try {
            $announcement = $this->contentService->updateAnnouncement(
                $announcement,
                $request->validated(),
                auth()->user()
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil diperbarui.',
                'data' => $announcement->load(['author', 'course']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('delete', $announcement);

        $this->contentService->deleteContent($announcement, auth()->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman berhasil dihapus.',
        ]);
    }

    /**
     * Publish the specified announcement.
     */
    public function publish(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('publish', $announcement);

        $this->contentService->publishContent($announcement);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman berhasil dipublikasikan.',
            'data' => $announcement->fresh(),
        ]);
    }

    /**
     * Schedule the specified announcement.
     */
    public function schedule(ScheduleContentRequest $request, int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->authorize('schedule', $announcement);

        try {
            $this->contentService->scheduleContent(
                $announcement,
                \Carbon\Carbon::parse($request->input('scheduled_at'))
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dijadwalkan.',
                'data' => $announcement->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark announcement as read.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        $this->contentService->markAsRead($announcement, auth()->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman ditandai sudah dibaca.',
        ]);
    }
}
