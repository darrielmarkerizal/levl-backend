<?php

namespace Modules\Gamification\Contracts\Repositories;

use Illuminate\Support\Collection;

interface UserBadgeRepositoryInterface
{
    /**
     * Count badges by user ID
     */
    public function countByUserId(int $userId): int;

    /**
     * Find badges by user ID with relations
     */
    public function findByUserId(int $userId): Collection;

    /**
     * Create user badge
     *
     * @return mixed
     */
    public function create(array $data);
}
