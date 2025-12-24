<?php

namespace Modules\Auth\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Auth\Models\PasswordResetToken;

interface PasswordResetTokenRepositoryInterface
{
    /**
     * Create a new password reset token
     */
    public function create(array $data): PasswordResetToken;

    /**
     * Find password reset tokens by email
     */
    public function findByEmail(string $email): Collection;

    /**
     * Delete password reset tokens by email
     */
    public function deleteByEmail(string $email): int;

    /**
     * Find valid tokens created within the specified time window
     */
    public function findValidTokens(int $ttlMinutes, int $limit = 100): Collection;
}
