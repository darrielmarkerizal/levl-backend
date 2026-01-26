<?php

declare(strict_types=1);

namespace Modules\Schemes\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Schemes\Models\Course;

interface CourseServiceInterface
{
    public function listPublic(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function listForIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function listPublicForIndex(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function create(array $data, ?\Modules\Auth\Models\User $actor = null, array $files = []): Course;
    public function update(int $id, array $data, array $files = []): ?Course;
    public function delete(int $id): bool;
    public function publish(int $id): ?Course;
    public function unpublish(int $id): ?Course;
    public function updateEnrollmentSettings(int $id, array $data): array;
    public function verifyEnrollmentKey(Course $course, string $plainKey): bool;
    public function generateEnrollmentKey(int $length = 12): string;
    public function hasEnrollmentKey(Course $course): bool; 
}
    

