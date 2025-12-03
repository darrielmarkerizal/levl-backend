<?php

namespace Modules\Content\Http\Controllers;

use App\Contracts\Services\ContentServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Http\Requests\CreateAnnouncementRequest;
use Modules\Content\Models\Announcement;
use Modules\Content\Repositories\AnnouncementRepository;
use Modules\Schemes\Models\Course;

class CourseAnnouncementController extends Controller
{
    protected ContentServiceInterface $contentService;

    protected AnnouncementRepository $announcementRepository;

    public function __construct(
        ContentServiceInterface $contentService,
        AnnouncementRepository $announcementRepository
    ) {
        $this->contentService = $contentService;
        $this->announcementRepository = $announcementRepository;
    }

    /**
     * Display announcements for a specific course.
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);

        $filters = [
            'per_page' => $request->input('per_page', 15),
        ];

        $announcements = $this->announcementRepository->getAnnouncementsForCourse($courseId, $filters);

        return response()->json([
            'status' => 'success',
            'data' => $announcements,
        ]);
    }

    /**
     * Store a new course announcement.
     */
    public function store(CreateAnnouncementRequest $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);

        $this->authorize('createCourseAnnouncement', [Announcement::class, $courseId]);

        try {
            $data = $request->validated();
            $data['course_id'] = $courseId;
            $data['target_type'] = 'course';

            $announcement = $this->contentService->createAnnouncement($data, auth()->user());

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
                'message' => 'Pengumuman kursus berhasil dibuat.',
                'data' => $announcement->load(['author', 'course']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
