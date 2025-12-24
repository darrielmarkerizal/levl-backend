<?php

namespace Modules\Auth\Repositories;

use Modules\Auth\Contracts\Repositories\PinnedBadgeRepositoryInterface;
use Modules\Auth\Models\PinnedBadge;

class PinnedBadgeRepository implements PinnedBadgeRepositoryInterface
{
    public function findByUserAndBadge(int $userId, int $badgeId): ?PinnedBadge
    {
        return PinnedBadge::where('user_id', $userId)
            ->where('badge_id', $badgeId)
            ->first();
    }

    public function create(array $data): PinnedBadge
    {
        return PinnedBadge::create($data);
    }

    public function delete(PinnedBadge $pinnedBadge): bool
    {
        return $pinnedBadge->delete();
    }
}
