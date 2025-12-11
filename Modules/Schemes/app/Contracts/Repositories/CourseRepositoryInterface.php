<?php

namespace Modules\Schemes\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Models\Course;

interface CourseRepositoryInterface
{
    public function findBySlug(string $slug): ?Course;

    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator;

    public function list(array $params = []): Collection;
}
