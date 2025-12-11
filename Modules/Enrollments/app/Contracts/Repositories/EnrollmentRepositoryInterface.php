<?php

namespace Modules\Enrollments\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Enrollments\Models\Enrollment;

interface EnrollmentRepositoryInterface
{
    /**
     * Paginate enrollments by course ID.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate enrollments by multiple course IDs.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByCourseIds(array $courseIds, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate enrollments by user ID.
     * Custom QueryFilter reads filter/sort from params or request.
     */
    public function paginateByUser(int $userId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find enrollment by course and user.
     */
    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment;
}
