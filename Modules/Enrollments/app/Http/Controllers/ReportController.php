<?php

namespace Modules\Enrollments\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Enrollments\Contracts\Services\EnrollmentReportServiceInterface;
use Modules\Enrollments\Exports\EnrollmentsExport;
use Modules\Schemes\Models\Course;

/**
 * @tags Laporan & Statistik
 */
class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly EnrollmentReportServiceInterface $reportService
    ) {}

    /**
     * Statistik Tingkat Penyelesaian Kursus
     *
     * Get completion statistics including total enrolled, active, completed, and completion percentage for a specific course.
     *
     *
     * @summary Statistik Tingkat Penyelesaian Kursus
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function courseCompletionRate(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $this->reportService->canUserManageCourse($user, $course)) {
            return $this->error(__('messages.enrollments.no_report_access'), 403);
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

    /**
     * Statistik Funnel Pendaftaran
     *
     * Get funnel statistics showing enrollment journey from request to completion.
     *
     *
     * @summary Statistik Funnel Pendaftaran
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function enrollmentFunnel(Request $request)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (
            ! $user->hasRole('Admin') &&
            ! $user->hasRole('Instructor') &&
            ! $user->hasRole('Superadmin')
        ) {
            return $this->error(__('messages.enrollments.no_report_view_access'), 403);
        }

        $courseId = $request->query('course_id');
        $funnel = $this->reportService->getEnrollmentFunnel($user, $courseId);

        return $this->success(['funnel' => $funnel]);
    }

    /**
     * Ekspor Data Pendaftaran CSV
     *
     * Export enrollment data for a course to CSV format.
     *
     *
     * @summary Ekspor Data Pendaftaran CSV
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example Report"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function exportEnrollmentsCsv(Request $request, Course $course)
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = auth('api')->user();

        if (! $this->reportService->canUserManageCourse($user, $course)) {
            return $this->error(__('messages.enrollments.no_export_access'), 403);
        }

        $query = $this->reportService->getDetailedEnrollmentsQuery($course);
        $filename = "enrollments-{$course->slug}-".now()->format('Y-m-d').'.csv';

        return Excel::download(new EnrollmentsExport($query), $filename);
    }
}
