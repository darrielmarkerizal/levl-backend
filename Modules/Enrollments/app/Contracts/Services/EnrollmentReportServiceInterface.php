<?php

declare(strict_types=1);

namespace Modules\Enrollments\Contracts\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

interface EnrollmentReportServiceInterface
{
    public function getCourseStatistics(Course $course): array;

    public function getEnrollmentFunnel(User $user, ?int $courseId = null): array;

    public function getDetailedEnrollmentsQuery(Course $course): Builder;

    public function canUserManageCourse(User $user, Course $course): bool;
}
