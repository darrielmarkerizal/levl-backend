<?php

declare(strict_types=1);

namespace Modules\Enrollments\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Enrollments\Models\Enrollment;

interface EnrollmentRepositoryInterface
{
    public function paginateByCourse(int $courseId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateByCourseIds(array $courseIds, array $params = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateByUser(int $userId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment;

    public function hasActiveEnrollment(int $userId, int $courseId): bool;

    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment;

    public function findActiveByUserAndCourse(int $userId, int $courseId): ?Enrollment;

    public function incrementLessonProgress(int $enrollmentId, int $lessonId): void;

    public function getStudentRoster(int $courseId): Collection;

    public function getEnrolledStudentIds(int $courseId): array;

    public function invalidateEnrollmentCache(int $courseId, int $userId): void;

    public function invalidateRosterCache(int $courseId): void;

    public function getCourseProgress(int $enrollmentId): float;
}
