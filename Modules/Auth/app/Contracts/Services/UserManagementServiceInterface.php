<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;

interface UserManagementServiceInterface
{
    public function listUsers(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function listUsersForIndex(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function showUser(User $authUser, int $userId): User;

    public function updateUserStatus(User $authUser, int $userId, string $status): User;

    public function deleteUser(User $authUser, int $userId): void;

    public function createUser(User $authUser, array $validated): User;

    public function updateProfile(User $user, array $validated, ?string $ip, ?string $userAgent): User;
}
