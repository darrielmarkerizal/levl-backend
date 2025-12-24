<?php

namespace Modules\Auth\Repositories;

use Illuminate\Support\Collection;
use Modules\Auth\Contracts\Repositories\PasswordResetTokenRepositoryInterface;
use Modules\Auth\Models\PasswordResetToken;

class PasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function create(array $data): PasswordResetToken
    {
        return PasswordResetToken::create($data);
    }

    public function findByEmail(string $email): Collection
    {
        return PasswordResetToken::where('email', $email)->get();
    }

    public function deleteByEmail(string $email): int
    {
        return PasswordResetToken::where('email', $email)->delete();
    }

    public function findValidTokens(int $ttlMinutes, int $limit = 100): Collection
    {
        return PasswordResetToken::query()
            ->where('created_at', '>=', now()->subMinutes($ttlMinutes + 5))
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
