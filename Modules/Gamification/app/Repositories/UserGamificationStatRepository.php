<?php

namespace Modules\Gamification\Repositories;

use Modules\Gamification\Contracts\Repositories\UserGamificationStatRepositoryInterface;
use Modules\Gamification\Models\UserGamificationStat;

class UserGamificationStatRepository implements UserGamificationStatRepositoryInterface
{
    public function findByUserId(int $userId): ?UserGamificationStat
    {
        return UserGamificationStat::where('user_id', $userId)->first();
    }

    public function create(array $data): UserGamificationStat
    {
        return UserGamificationStat::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        return UserGamificationStat::where('user_id', $userId)->update($data);
    }
}
