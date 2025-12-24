<?php

namespace Modules\Gamification\Contracts\Repositories;

use Modules\Gamification\Models\UserGamificationStat;

interface UserGamificationStatRepositoryInterface
{
    /**
     * Find user gamification stats by user ID
     */
    public function findByUserId(int $userId): ?UserGamificationStat;

    /**
     * Create user gamification stats
     */
    public function create(array $data): UserGamificationStat;

    /**
     * Update user gamification stats
     */
    public function update(int $userId, array $data): bool;
}
