<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Schemes\Models\Tag;

class TagPolicy
{
    /**
     * Determine if the user can view any tags.
     * Tags are public, so anyone can view them.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view a specific tag.
     * Tags are public, so anyone can view them.
     */
    public function view(?User $user, Tag $tag): bool
    {
        return true;
    }

    /**
     * Determine if the user can create tags.
     * Only Superadmin can create tags.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    /**
     * Determine if the user can update a tag.
     * Only Superadmin can update tags.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->hasRole('Superadmin');
    }

    /**
     * Determine if the user can delete a tag.
     * Only Superadmin can delete tags.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasRole('Superadmin');
    }
}
