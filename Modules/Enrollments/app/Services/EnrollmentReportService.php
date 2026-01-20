<?php

namespace Modules\Enrollments\Services;

use Modules\Auth\Models\User;
use Modules\Enrollments\Contracts\Services\EnrollmentReportServiceInterface;
use Modules\Enrollments\Models\CourseProgress;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class EnrollmentReportService implements EnrollmentReportServiceInterface
{
    public function getCourseStatistics(Course $course): array
    {
        $stats = Enrollment::query()
            ->where('course_id', $course->id)
            ->selectRaw("
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
            ")
            ->first();

        $totalEnrolled = max(1, $stats->total_enrolled);
        $completionRate = round(($stats->completed_count / $totalEnrolled) * 100, 2);

        $avgProgress = CourseProgress::query()
            ->join('enrollments', 'course_progress.enrollment_id', '=', 'enrollments.id')
            ->where('enrollments.course_id', $course->id)
            ->whereIn('enrollments.status', ['active', 'completed'])
            ->avg('course_progress.progress_percent') ?? 0;

        return [
            'total_enrolled' => (int) $stats->total_enrolled,
            'active_count' => (int) $stats->active_count,
            'completed_count' => (int) $stats->completed_count,
            'pending_count' => (int) $stats->pending_count,
            'cancelled_count' => (int) $stats->cancelled_count,
            'completion_rate' => $completionRate,
            'avg_progress_percent' => round($avgProgress, 2),
        ];
    }

    public function getEnrollmentFunnel(User $user, ?int $courseId = null): array
    {
        $query = Enrollment::query();

        if ($courseId) {
            $query->where('course_id', $courseId);
        } elseif (! $user->hasRole('Superadmin')) {
            
            $courseIds = Course::query()
                ->where(function ($q) use ($user) {
                    $q->where('instructor_id', $user->id)
                        ->orWhereHas('admins', fn ($aq) => $aq->where('user_id', $user->id));
                })
                ->pluck('id');
            $query->whereIn('course_id', $courseIds);
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        ")->first();

        $total = max(1, $stats->total_requests);

        return [
            'total_requests' => (int) $stats->total_requests,
            'pending' => [
                'count' => (int) $stats->pending,
                'percentage' => round(($stats->pending / $total) * 100, 2),
            ],
            'active' => [
                'count' => (int) $stats->active,
                'percentage' => round(($stats->active / $total) * 100, 2),
            ],
            'completed' => [
                'count' => (int) $stats->completed,
                'percentage' => round(($stats->completed / $total) * 100, 2),
            ],
            'cancelled' => [
                'count' => (int) $stats->cancelled,
                'percentage' => round(($stats->cancelled / $total) * 100, 2),
            ],
        ];
    }

    public function getDetailedEnrollmentsQuery(Course $course)
    {
        return Enrollment::query()
            ->where('course_id', $course->id)
            ->with(['user:id,name,email', 'courseProgress'])
            ->orderByDesc('created_at');
    }

    public function canUserManageCourse(User $user, Course $course): bool
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($user->hasRole('Admin') || $user->hasRole('Instructor')) {
            if ((int) $course->instructor_id === (int) $user->id) {
                return true;
            }

            if (method_exists($course, 'hasAdmin') && $course->hasAdmin($user)) {
                return true;
            }
        }

        return false;
    }
}
