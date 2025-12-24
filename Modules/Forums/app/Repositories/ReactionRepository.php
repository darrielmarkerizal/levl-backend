<?php

namespace Modules\Forums\Repositories;

use Modules\Forums\Contracts\Repositories\ReactionRepositoryInterface;
use Modules\Forums\Models\Reaction;

class ReactionRepository implements ReactionRepositoryInterface
{
    public function findByUserAndReactable(int $userId, string $reactableType, int $reactableId): ?Reaction
    {
        return Reaction::where([
            'user_id' => $userId,
            'reactable_type' => $reactableType,
            'reactable_id' => $reactableId,
        ])->first();
    }

    public function create(array $data): Reaction
    {
        return Reaction::create($data);
    }

    public function delete(Reaction $reaction): bool
    {
        return $reaction->delete();
    }
}
