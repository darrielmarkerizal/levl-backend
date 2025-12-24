<?php

namespace Modules\Gamification\Repositories;

use Illuminate\Support\Collection;
use Modules\Gamification\Contracts\Repositories\UserBadgeRepositoryInterface;
use Modules\Gamification\Models\UserBadge;

class UserBadgeRepository implements UserBadgeRepositoryInterface
{
    public function countByUserId(int $userId): int
    {
        return UserBadge::where('user_id', $userId)->count();
    }

    public function findByUserId(int $userId): Collection
    {
        return UserBadge::with(['badge', 'badge.media'])
            ->where('user_id', $userId)
            ->orderByDesc('awarded_at')
            ->get();
    }

    public function create(array $data)
    {
        return UserBadge::create($data);
    }
}
