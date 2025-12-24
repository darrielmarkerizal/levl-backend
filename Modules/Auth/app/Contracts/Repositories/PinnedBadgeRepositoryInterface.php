<?php

namespace Modules\Auth\Contracts\Repositories;

use Modules\Auth\Models\PinnedBadge;

interface PinnedBadgeRepositoryInterface
{
    /**
     * Find a pinned badge by user ID and badge ID
     */
    public function findByUserAndBadge(int $userId, int $badgeId): ?PinnedBadge;

    /**
     * Create a new pinned badge
     */
    public function create(array $data): PinnedBadge;

    /**
     * Delete a pinned badge
     */
    public function delete(PinnedBadge $pinnedBadge): bool;
}
