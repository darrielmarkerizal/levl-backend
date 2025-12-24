<?php

namespace Modules\Gamification\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PointRepositoryInterface
{
    /**
     * Paginate points by user ID
     */
    public function paginateByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create point record
     *
     * @return mixed
     */
    public function create(array $data);

    /**
     * Sum points by user ID
     */
    public function sumByUserId(int $userId): int;
}
