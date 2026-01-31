<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Enrollments\Contracts\Services\EnrollmentReportServiceInterface;
use Modules\Enrollments\Exports\EnrollmentsExport;
use Modules\Schemes\Models\Course;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EnrollmentReportServiceInterface $reportService
    ) {}

    public function courseCompletionRate(Request $request, Course $course)
    {
        $user = auth('api')->user();

        if (! $this->reportService->canUserManageCourse($user, $course)) {
            return $this->forbidden(__('messages.enrollments.no_report_access'));
        }

        $statistics = $this->reportService->getCourseStatistics($course);

        return $this->success([
            'course' => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
            ],
            'statistics' => $statistics,
        ]);
    }

    public function enrollmentFunnel(Request $request)
    {
        $user = auth('api')->user();

        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->forbidden(__('messages.enrollments.no_report_view_access'));
        }

        $courseId = $request->query('course_id');
        $funnel = $this->reportService->getEnrollmentFunnel($user, $courseId);

        return $this->success(['funnel' => $funnel]);
    }

    public function exportEnrollmentsCsv(Request $request, Course $course)
    {
        $user = auth('api')->user();

        if (! $this->reportService->canUserManageCourse($user, $course)) {
            return $this->forbidden(__('messages.enrollments.no_export_access'));
        }

        $query = $this->reportService->getDetailedEnrollmentsQuery($course);
        $filename = "enrollments-{$course->slug}-".now()->format('Y-m-d').'.csv';

        return Excel::download(new EnrollmentsExport($query), $filename);
    }
}
