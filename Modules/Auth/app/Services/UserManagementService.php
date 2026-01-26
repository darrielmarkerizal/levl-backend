<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\UserAccessPolicyInterface;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Contracts\Services\UserManagementServiceInterface;
use Modules\Common\Models\Audit;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\CourseAdmin;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UserManagementService implements UserManagementServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly UserAccessPolicyInterface $userAccessPolicy,
        private readonly UserCacheService $cacheService,
    ) {}

    public function listUsers(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->listUsersForIndex($authUser, $perPage, $search);
    }

    public function listUsersForIndex(User $authUser, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        if (!$authUser->can('viewAny', User::class)) {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $query = QueryBuilder::for(User::class)
            ->select(['id', 'name', 'email', 'username', 'status', 'account_status', 'created_at', 'email_verified_at', 'is_password_set'])
            ->with(['roles:id,name,guard_name', 'media:id,model_type,model_id,collection_name,file_name,disk']);

        if ($search && trim($search) !== '') {
            $ids = User::search($search)->keys()->toArray();
            $query->whereIn('id', $ids);
        }

        if ($authUser->hasRole('Admin') && !$authUser->hasRole('Superadmin')) {
            $managedCourseIds = CourseAdmin::query()
                ->where('user_id', $authUser->id)
                ->pluck('course_id')
                ->unique();

            $query->where(function (Builder $q) use ($managedCourseIds) {
                $q->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Admin');
                })
                ->orWhere(function ($subQuery) use ($managedCourseIds) {
                    $subQuery->whereHas('roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['Instructor', 'Student']);
                    })
                    ->whereHas('enrollments', function ($enrollmentQuery) use ($managedCourseIds) {
                        $enrollmentQuery->whereIn('course_id', $managedCourseIds);
                    });
                });
            });
        }

        return $query->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::callback('role', function (Builder $query, $value) {
                    $roles = is_array($value)
                      ? $value
                      : Str::of($value)->explode(',')->map(fn ($r) => trim($r))->toArray();
                    $query->whereHas('roles', fn ($q) => $q->whereIn('name', $roles));
                }),
                AllowedFilter::callback('search', function (Builder $query, $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $ids = User::search($value)->keys()->toArray();
                        $query->whereIn($query->getModel()->getTable().'.id', $ids);
                    }
                }),
            ])
            ->allowedSorts(['name', 'email', 'username', 'status', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }

    public function showUser(User $authUser, int $userId): User
    {
        
        $target = $this->cacheService->getUser($userId);
        
        if (!$target) {
            $target = User::findOrFail($userId);
        }
        
        
        if (!$authUser->can('view', $target)) {
            throw new AuthorizationException(__('messages.auth.no_access_to_user'));
        }

        return $target;
    }

    public function updateUserStatus(User $authUser, int $userId, string $status): User
    {
        $user = $this->showUser($authUser, $userId);

        if ($status === UserStatus::Pending->value) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_pending')],
            ]);
        }

        if ($user->status === UserStatus::Pending) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => [__('messages.auth.status_cannot_be_changed_from_pending')],
            ]);
        }

        return DB::transaction(function () use ($user, $status) {
            $user->status = UserStatus::from($status);
            $user->save();
            
            
            $this->cacheService->invalidateUser($user->id);
            
            return $user->fresh();
        });
    }

    public function deleteUser(User $authUser, int $userId): void
    {
        $user = $this->showUser($authUser, $userId);

        if ($user->id === $authUser->id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account' => [__('messages.auth.cannot_delete_self')],
            ]);
        }

        if (!$authUser->hasRole('Superadmin')) {
            
            
            if ($user->hasRole('Superadmin')) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        }

        $user->delete();
    }

    public function createUser(User $authUser, array $validated): User
    {
        $role = $validated['role'];

        
        if ($role === config('auth.default_role', 'Student')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => [__('messages.auth.student_creation_forbidden')],
            ]);
        }

        
        if ($authUser->hasRole('Admin') && !$authUser->hasRole('Superadmin')) {
            
            if (!in_array($role, ['Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } elseif ($authUser->hasRole('Superadmin')) {
            
            if (!in_array($role, ['Superadmin', 'Admin', 'Instructor'])) {
                throw new AuthorizationException(__('messages.forbidden'));
            }
        } else {
            throw new AuthorizationException(__('messages.unauthorized'));
        }

        $passwordPlain = Str::random(12);
        unset($validated['role']);
        $validated['password'] = \Illuminate\Support\Facades\Hash::make($passwordPlain);
        
        $user = $this->authRepository->createUser($validated + ['is_password_set' => false]);
        $user->assignRole($role);

        
        
        return $user;
    }

    public function updateProfile(User $user, array $validated, ?string $ip, ?string $userAgent): User
    {
        return DB::transaction(function () use ($user, $validated, $ip, $userAgent) {
            $changes = [];
            foreach (['name', 'username'] as $field) {
                if (isset($validated[$field]) && $user->{$field} !== $validated[$field]) {
                    $changes[$field] = [$user->{$field}, $validated[$field]];
                    $user->{$field} = $validated[$field];
                }
            }

            
            
            
            
            
            
            $user->save();
            
            if (!empty($changes)) {
                 dispatch(new \App\Jobs\CreateAuditJob([
                    'action' => 'update',
                    'user_id' => $user->id,
                    'module' => 'Auth',
                    'target_table' => 'users',
                    'target_id' => $user->id,
                    'meta' => ['action' => 'profile.update', 'changes' => $changes],
                    'logged_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent
                ]));
            }
            
            return $user->fresh();
        });
    }
}
