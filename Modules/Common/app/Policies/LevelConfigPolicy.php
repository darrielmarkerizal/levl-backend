<?php

declare(strict_types=1);

namespace Modules\Common\Policies;

use Modules\Auth\Models\User;
use Modules\Common\Models\LevelConfig;

class LevelConfigPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, LevelConfig $levelConfig): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function update(User $user, LevelConfig $levelConfig): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function delete(User $user, LevelConfig $levelConfig): bool
    {
        return $user->hasRole('Superadmin');
    }
}
