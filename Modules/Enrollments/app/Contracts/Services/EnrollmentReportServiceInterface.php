<?php

namespace Modules\Enrollments\Contracts\Services;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

interface EnrollmentReportServiceInterface
{
    /**
     * Get course completion statistics
     *
     * @return array{
     *     total_enrolled: int,
     *     active_count: int,
     *     completed_count: int,
     *     pending_count: int,
     *     cancelled_count: int,
     *     completion_rate: float,
     *     avg_progress_percent: float
     * }
     */
    public function getCourseStatistics(Course $course): array;

    /**
     * Get enrollment funnel statistics
     *
     * @return array{
     *     total_requests: int,
     *     pending: array{count: int, percentage: float},
     *     active: array{count: int, percentage: float},
     *     completed: array{count: int, percentage: float},
     *     cancelled: array{count: int, percentage: float}
     * }
     */
    public function getEnrollmentFunnel(User $user, ?int $courseId = null): array;

    /**
     * Get detailed enrollments query for export
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getDetailedEnrollmentsQuery(Course $course);

    /**
     * Check if user can manage course
     */
    public function canUserManageCourse(User $user, Course $course): bool;
}
