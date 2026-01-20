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
        $currentUser = auth('api')->user();

        // Validate user exists
        $targetUser = \Modules\Auth\Models\User::find($userId);
        if (! $targetUser) {
            return $this->notFound(__('messages.users.not_found'));
        }

        // Check if user is enrolled in the course
        $enrollment = \Modules\Enrollments\Models\Enrollment::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment) {
            return $this->notFound(__('messages.progress.enrollment_not_found'));
        }

        // If viewing someone else's progress, check authorization
        if ($userId !== (int) auth('api')->id()) {
            $canView = $currentUser->hasRole('Superadmin')
                || ($currentUser->hasRole('Instructor') && $course->instructor_id === $currentUser->id)
                || ($currentUser->hasRole('Admin') && $course->admins()->where('user_id', $currentUser->id)->exists());

            if (! $canView) {
                $this->authorize('viewAny', [\Modules\Enrollments\Models\Enrollment::class, $course]);
            }
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

    public function uncompleteLesson(Request $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $enrollment = $this->progression->validateLessonAccess($course, $unit, $lesson, (int) auth('api')->id());
        $this->progression->markLessonUncompleted($lesson, $enrollment);
        
        return $this->success(
            new ProgressResource($this->progression->getCourseProgressData($course, $enrollment)), 
            __('messages.progress.updated')
        );
    }
}
