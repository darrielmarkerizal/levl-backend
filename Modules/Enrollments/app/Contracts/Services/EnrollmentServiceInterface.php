<?php

namespace Modules\Enrollments\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Enrollments\DTOs\CreateEnrollmentDTO;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

interface EnrollmentServiceInterface
{
    public function paginateByCourse(int $courseId, int $perPage = 15): LengthAwarePaginator;

    public function paginateByCourseIds(array $courseIds, int $perPage = 15): LengthAwarePaginator;

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Enrollment;

    public function findByCourseAndUser(int $courseId, int $userId): ?Enrollment;

    public function enroll(Course $course, User $user, CreateEnrollmentDTO $dto): array;

    public function cancel(Enrollment $enrollment): Enrollment;

    public function withdraw(Enrollment $enrollment): Enrollment;

    public function approve(Enrollment $enrollment): Enrollment;

    public function decline(Enrollment $enrollment): Enrollment;

    public function remove(Enrollment $enrollment): Enrollment;

    /**
     * Check if user is enrolled in a course with active or completed status
     */
    public function isUserEnrolledInCourse(int $userId, int $courseId): bool;

    /**
     * Get active enrollment for user in a course
     */
    public function getActiveEnrollment(int $userId, int $courseId): ?Enrollment;
}
