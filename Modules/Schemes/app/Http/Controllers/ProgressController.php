<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Resources\ProgressResource;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\ProgressionService;

class ProgressController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private readonly ProgressionService $progression) {}

    public function show(Request $request, Course $course)
    {
        $userId = (int) ($request->query('user_id') ?? auth('api')->id());
        if ($userId !== (int) auth('api')->id()) {
            $this->authorize('viewAny', [\Modules\Enrollments\Models\Enrollment::class, $course]);
        }

        $result = $this->progression->getProgressForUser($course, $userId);
        return $this->success(new ProgressResource($result));
    }

    public function completeLesson(Request $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $enrollment = $this->progression->validateLessonAccess($course, $unit, $lesson, (int) auth('api')->id());
        $this->progression->markLessonCompleted($lesson, $enrollment);
        
        return $this->success(
            new ProgressResource($this->progression->getCourseProgressData($course, $enrollment)), 
            __('messages.progress.updated')
        );
    }
}
