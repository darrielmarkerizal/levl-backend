<?php

declare(strict_types=1);

namespace Modules\Common\Policies;

use Modules\Auth\Models\User;
use Modules\Gamification\Models\Challenge;

class AchievementPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Challenge $achievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function update(User $user, Challenge $achievement): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function delete(User $user, Challenge $achievement): bool
    {
        return $user->hasRole('Superadmin');
    }
}
