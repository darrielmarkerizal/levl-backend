<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Auth\Models\User;

class UserCacheService
{
    private const TTL_USER = 3600;

    private const TTL_ROLES = 3600;

    private const TTL_PERMISSIONS = 3600;

    public function getUser(int $id): ?User
    {
        return Cache::tags(['users'])
            ->remember("user.{$id}", self::TTL_USER, function () use ($id) {
                return User::select([
                    'id', 'name', 'email', 'username',
                    'status', 'account_status', 'created_at', 'email_verified_at',
                    'is_password_set',
                ])
                    ->with(['roles:id,name,guard_name', 'media'])
                    ->find($id);
            });
    }

    public function getUserByEmail(string $email): ?User
    {
        return Cache::tags(['users'])
            ->remember("user.email.{$email}", 300, function () use ($email) {
                return User::where('email', $email)
                    ->with(['roles:id,name'])
                    ->first();
            });
    }

    public function getUserByUsername(string $username): ?User
    {
        return Cache::tags(['users'])
            ->remember("user.username.{$username}", 300, function () use ($username) {
                return User::where('username', $username)
                    ->with(['roles:id,name'])
                    ->first();
            });
    }

    public function getUserRoles(int $userId): array
    {
        return Cache::tags(['users', 'roles'])
            ->remember("user.{$userId}.roles", self::TTL_ROLES, function () use ($userId) {
                return User::with('roles:id,name')->find($userId)?->roles->pluck('name')->toArray() ?? [];
            });
    }

    public function getUserPermissions(int $userId): array
    {
        return Cache::tags(['users', 'permissions'])
            ->remember("user.{$userId}.permissions", self::TTL_PERMISSIONS, function () use ($userId) {
                return User::with('permissions')->find($userId)?->getAllPermissions()->pluck('name')->toArray() ?? [];
            });
    }

    public function invalidateUser(int $userId): void
    {
        Cache::tags(['users'])->forget("user.{$userId}");
        Cache::tags(['users'])->forget("user.{$userId}.roles");
        Cache::tags(['users'])->forget("user.{$userId}.permissions");
        Cache::tags(['users'])->forget("user.{$userId}.stats");
    }

    public function invalidateUserByEmail(string $email): void
    {
        Cache::tags(['users'])->forget("user.email.{$email}");
    }

    public function invalidateUserByUsername(string $username): void
    {
        Cache::tags(['users'])->forget("user.username.{$username}");
    }

    public function invalidateAllUsers(): void
    {
        Cache::tags(['users'])->flush();
    }
}
