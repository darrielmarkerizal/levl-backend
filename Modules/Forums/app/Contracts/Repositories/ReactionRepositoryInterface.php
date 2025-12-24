<?php

namespace Modules\Forums\Contracts\Repositories;

use Modules\Forums\Models\Reaction;

interface ReactionRepositoryInterface
{
    /**
     * Find a reaction by user and reactable (polymorphic)
     */
    public function findByUserAndReactable(int $userId, string $reactableType, int $reactableId): ?Reaction;

    /**
     * Create a new reaction
     */
    public function create(array $data): Reaction;

    /**
     * Delete a reaction
     */
    public function delete(Reaction $reaction): bool;
}
