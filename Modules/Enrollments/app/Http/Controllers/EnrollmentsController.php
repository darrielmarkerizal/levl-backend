<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Enrollments\DTOs\CreateEnrollmentDTO;
use Modules\Enrollments\Http\Requests\EnrollRequest;
use Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface;
use Modules\Enrollments\Http\Resources\EnrollmentResource;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class EnrollmentsController extends Controller
{
    use ApiResponse;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(private readonly EnrollmentServiceInterface $service) {}

    public function index(Request $request)
    {
        $user = auth('api')->user();
        $perPage = max(1, (int) $request->query('per_page', 15));
        $courseSlug = $request->input('filter.course_slug');
        
        $paginator = $this->service->listEnrollments($user, $perPage, $courseSlug);
        
        $paginator->getCollection()->transform(fn($item) => new EnrollmentResource($item));
        return $this->paginateResponse($paginator, __('messages.enrollments.list_retrieved'));
    }

    public function indexByCourse(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $perPage = max(1, (int) $request->query('per_page', 15));
        $paginator = $this->service->paginateByCourse($course->id, $perPage);

        $paginator->getCollection()->transform(fn($item) => new EnrollmentResource($item));
        return $this->paginateResponse($paginator, __('messages.enrollments.course_list_retrieved'));
    }

    public function enroll(EnrollRequest $request, Course $course)
    {
        $user = auth('api')->user();
        
        $dto = CreateEnrollmentDTO::fromRequest([
            'course_id' => $course->id,
            'enrollment_key' => $request->input('enrollment_key'),
        ]);
        
        $result = $this->service->enroll($course, $user, $dto);
        
        return $this->success(new EnrollmentResource($result['enrollment']), $result['message']);
    }

    public function cancel(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $targetUserId = $user->hasRole('Superadmin') ? $request->input('user_id') : null;
        
        $enrollment = $this->service->cancelEnrollment($course, $user, $targetUserId);
        $this->authorize('cancel', $enrollment);
        
        $updated = $this->service->cancel($enrollment);
        
        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.cancelled'));
    }

    public function withdraw(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $targetUserId = $user->hasRole('Superadmin') ? $request->input('user_id') : null;
        
        $enrollment = $this->service->withdrawEnrollment($course, $user, $targetUserId);
        $this->authorize('withdraw', $enrollment);
        
        $updated = $this->service->withdraw($enrollment);
        
        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.withdrawn'));
    }

    public function status(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $targetUserId = $user->hasRole('Superadmin') ? $request->query('user_id') : null;
        
        $result = $this->service->getEnrollmentStatus($course, $user, $targetUserId);
        
        if (! $result['found']) {
            return $this->success(
                ['status' => 'not_enrolled', 'enrollment' => null],
                __('messages.enrollments.not_enrolled'),
            );
        }
        
        $this->authorize('view', $result['enrollment']);
        
        $enrollment = $result['enrollment']->fresh(['course:id,title,slug', 'user:id,name,email']);
        
        return $this->success(
            ['status' => $enrollment->status, 'enrollment' => new EnrollmentResource($enrollment)],
            __('messages.enrollments.status_retrieved'),
        );
    }

    public function approve(Enrollment $enrollment)
    {
        $this->authorize('approve', $enrollment);
        
        $updated = $this->service->approve($enrollment);
        
        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.approved'));
    }

    public function decline(Enrollment $enrollment)
    {
        $this->authorize('decline', $enrollment);
        
        $updated = $this->service->decline($enrollment);
        
        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.rejected'));
    }

    public function remove(Enrollment $enrollment)
    {
        $this->authorize('remove', $enrollment);
        
        $updated = $this->service->remove($enrollment);
        
        return $this->success(new EnrollmentResource($updated), __('messages.enrollments.expelled'));
    }
}
