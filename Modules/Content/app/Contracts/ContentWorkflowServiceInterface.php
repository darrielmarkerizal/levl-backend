<?php

namespace Modules\Content\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Content\Exceptions\InvalidTransitionException;

interface ContentWorkflowServiceInterface
{
    /**
     * Transition content to new state.
     *
     * @throws InvalidTransitionException
     */
    public function transition(Model $content, string $newState, User $user, ?string $note = null): bool;

    /**
     * Check if transition is valid.
     */
    public function canTransition(string $currentState, string $newState): bool;

    /**
     * Get allowed transitions for current state.
     */
    public function getAllowedTransitions(string $currentState): array;
}
