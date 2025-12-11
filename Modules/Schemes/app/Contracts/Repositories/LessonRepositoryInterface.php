<?php

namespace Modules\Schemes\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Models\Lesson;

interface LessonRepositoryInterface
{
    /**
     * Find lessons by unit ID with pagination.
     * Uses custom QueryFilter for filter/sort from params or request.
     *
     * @param  int  $unitId  Unit ID
     * @param  array  $params  Filter/sort parameters
     * @param  int  $perPage  Items per page
     */
    public function findByUnit(int $unitId, array $params = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a lesson by unit ID and lesson ID.
     *
     * @param  int  $unitId  Unit ID
     * @param  int  $id  Lesson ID
     */
    public function findByUnitAndId(int $unitId, int $id): ?Lesson;

    /**
     * Get the maximum order value for lessons in a unit.
     *
     * @param  int  $unitId  Unit ID
     */
    public function getMaxOrderForUnit(int $unitId): int;

    /**
     * Get all lessons for a unit.
     *
     * @param  int  $unitId  Unit ID
     */
    public function getAllByUnit(int $unitId): Collection;
}
