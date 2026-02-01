<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\Services\AuthServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;
use Tymon\JWTAuth\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt,
        private readonly EmailVerificationService $emailVerification,
        private readonly LoginThrottlingService $throttle,
    ) {}

    public function register(RegisterDTO|array $data, string $ip, ?string $userAgent): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $ip, $userAgent) {
            $validated = $data instanceof RegisterDTO ? $data->toArray() : $data;
            $validated['password'] = Hash::make($validated['password']);
            $user = $this->authRepository->createUser($validated);

            $user->assignRole(config('auth.default_role', 'Student'));

            $token = $this->jwt->fromUser($user);

            $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
            $refresh = $this->authRepository->createRefreshToken(
                userId: $user->id,
                ip: $ip,
                userAgent: $userAgent,
                deviceId: $deviceId,
            );

            $pair = new TokenPairDTO(
                accessToken: $token,
                expiresIn: $this->jwt->factory()->getTTL() * 60,
                refreshToken: $refresh->getAttribute('plain_token'),
            );

            $verificationUuid = $this->emailVerification->sendVerificationLink($user);

            $userArray = $user->toArray();
            $userArray['roles'] = $user->getRoleNames()->values();
            $userArray['avatar_url'] = $user->avatar_url;

            $response = ['user' => $userArray] + $pair->toArray();

            if ($verificationUuid) {
                $response['verification_uuid'] = $verificationUuid;
            }

            return $response;
        });
    }

    public function login(
        LoginDTO|string $loginOrDto,
        ?string $password,
        string $ip,
        ?string $userAgent,
    ): array {
        if ($loginOrDto instanceof LoginDTO) {
            $login = $loginOrDto->login;
            $password = $loginOrDto->password;
        } else {
            $login = $loginOrDto;
        }

        $this->throttle->ensureNotLocked($login);
        if ($this->throttle->tooManyAttempts($login, $ip)) {
            $retryAfter = $this->throttle->getRetryAfterSeconds($login, $ip);
            $cfg = $this->throttle->getRateLimitConfig();
            $m = intdiv($retryAfter, 60);
            $s = $retryAfter % 60;
            $retryIn = $m > 0 ? $m.' menit'.($s > 0 ? ' '.$s.' detik' : '') : $s.' detik';
            throw ValidationException::withMessages([
                'login' => __('messages.auth.throttle_message', ['max' => $cfg['max'], 'decay' => $cfg['decay'], 'retryIn' => $retryIn]),
            ]);
        }

        $user = $this->authRepository->findByLogin($login);
        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw new BusinessException(
                __('messages.auth.invalid_credentials'),
                ['login' => [__('messages.auth.invalid_credentials')]],
                401,
            );
        }

        $roles = $user->getRoleNames();
        $isPrivileged = $roles->intersect(['Superadmin', 'Admin', 'Instructor'])->isNotEmpty();

        $wasAutoVerified = false;
        if (
            $isPrivileged &&
            ($user->status === UserStatus::Pending || $user->email_verified_at === null)
        ) {
            $user->email_verified_at = now();
            $user->status = UserStatus::Active;
            $user->save();
            $user->refresh();
            $wasAutoVerified = true;
        }

        $token = $this->jwt->fromUser($user);

        $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId,
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token'),
        );

        $this->throttle->clearAttempts($login, $ip);

        dispatch(new \App\Jobs\LogActivityJob([
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'properties' => [
                'action' => 'login',
                'login_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username',
                'auto_verified' => $wasAutoVerified,
                'status' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            ],
            'description' => __('messages.auth.log_user_login'),
        ]));

        $userArray = $user->toArray();
        $userArray['roles'] = $roles->values();
        $userArray['avatar_url'] = $user->avatar_url;
        $userArray['status'] = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;

        $response = ['user' => $userArray] + $pair->toArray();

        if (
            $user->status === UserStatus::Pending &&
            $user->email_verified_at === null &&
            ! $isPrivileged
        ) {
            $verificationUuid = $this->emailVerification->sendVerificationLink($user);
            $response['status'] = UserStatus::Pending->value;
            $response['message'] = __('messages.auth.email_not_verified');
            if ($verificationUuid) {
                $response['verification_uuid'] = $verificationUuid;
            }
        } elseif ($user->status === UserStatus::Inactive) {
            $response['status'] = UserStatus::Inactive->value;
            $response['message'] = __('messages.auth.account_not_active_contact_admin');
        } elseif ($user->status === UserStatus::Banned) {
            $response['status'] = UserStatus::Banned->value;
            $response['message'] = __('messages.auth.account_banned_contact_admin');
        } elseif ($wasAutoVerified) {
            $response['message'] = __('messages.auth.login_success_auto_verified');
        }

        return $response;
    }

    public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
    {
        $record = $this->authRepository->findValidRefreshRecord($refreshToken);
        if (! $record) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_invalid'),
            ]);
        }

        $user = $record->user;
        if (! $user) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_user_not_found'),
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.account_not_active'),
            ]);
        }

        if ($record->isReplaced()) {
            $chain = $this->authRepository->findReplacedTokenChain($record->id);
            $deviceIds = collect($chain)->pluck('device_id')->unique()->filter()->toArray();

            foreach ($deviceIds as $deviceId) {
                $this->authRepository->revokeAllUserRefreshTokensByDevice($user->id, $deviceId);
            }

            throw ValidationException::withMessages([
                'refresh_token' => __('messages.auth.refresh_token_compromised'),
            ]);
        }

        $deviceId = $record->device_id ?? hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);

        $newRefresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            deviceId: $deviceId,
        );

        $this->authRepository->markTokenAsReplaced($record->id, $newRefresh->id);

        $record->update([
            'last_used_at' => now(),
            'idle_expires_at' => now()->addDays(14),
        ]);

        $accessToken = $this->jwt->fromUser($user);

        return [
            'access_token' => $accessToken,
            'expires_in' => $this->jwt->factory()->getTTL() * 60,
            'refresh_token' => $newRefresh->getAttribute('plain_token'),
        ];
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        dispatch(new \App\Jobs\LogActivityJob([
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'properties' => [
                'action' => 'logout',
                'refresh_token_revoked' => $refreshToken !== null,
            ],
            'description' => __('messages.auth.log_user_logout'),
        ]));

        $this->jwt->setToken($currentJwt)->invalidate();
        if ($refreshToken) {
            $this->authRepository->revokeRefreshToken($refreshToken, $user->id);
        }
    }

    public function me(User $user): User
    {
        return $user;
    }

    public function verifyEmail(string $token, string $uuid): array
    {
        return $this->emailVerification->verifyByToken($token, $uuid);
    }

    public function sendEmailVerificationLink(User $user): ?string
    {
        return $this->emailVerification->sendVerificationLink($user);
    }

    public function setUsername(User $user, string $username): array
    {
        $user->update(['username' => $username]);
        $user->refresh();

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();
        $userArray['avatar_url'] = $user->avatar_url;

        return ['user' => $userArray];
    }

    public function setPassword(User $user, string $password): array
    {
        $user->update([
            'password' => Hash::make($password),
            'is_password_set' => true,
        ]);
        $user->refresh();

        \Illuminate\Support\Facades\Cache::tags(['user_profile'])->forget('user_profile:'.$user->id);

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();
        $userArray['avatar_url'] = $user->avatar_url;

        return ['user' => $userArray];
    }

    public function logProfileUpdate(
        User $user,
        array $changes,
        ?string $ip,
        ?string $userAgent,
    ): void {
        dispatch(new \App\Jobs\CreateAuditJob([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => ['action' => 'profile.update', 'changes' => $changes],
            'logged_at' => now(),
        ]));
    }

    public function logEmailChangeRequest(
        User $user,
        string $newEmail,
        string $uuid,
        ?string $ip,
        ?string $userAgent,
    ): void {
        dispatch(new \App\Jobs\CreateAuditJob([
            'action' => 'update',
            'user_id' => $user->id,
            'module' => 'Auth',
            'target_table' => 'users',
            'target_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'meta' => [
                'action' => 'email.change.request',
                'new_email' => $newEmail,
                'uuid' => $uuid,
            ],
            'logged_at' => now(),
        ]));
    }

    public function createUserFromGoogle($googleUser): User
    {
        $user = $this->authRepository->createUser([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'username' => null,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ]);

        $user->assignRole(config('auth.default_role', 'Student'));

        return $user;
    }

    public function generateDevTokens(string $ip, ?string $userAgent): array
    {
        $roles = ['Student', 'Instructor', 'Admin', 'Superadmin'];
        $tokens = [];

        foreach ($roles as $role) {
            $user = User::where('email', strtolower($role).'@example.com')->first();

            if (! $user) {
                $user = $this->authRepository->createUser([
                    'name' => $role,
                    'email' => strtolower($role).'@example.com',
                    'username' => strtolower($role),
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => UserStatus::Active,
                ]);

                $user->assignRole($role);
            }

            $originalTTL = $this->jwt->factory()->getTTL();
            $this->jwt->factory()->setTTL(525600);

            $token = $this->jwt->fromUser($user);
            $deviceId = hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
            $refresh = $this->authRepository->createRefreshToken(
                userId: $user->id,
                ip: $ip,
                userAgent: $userAgent,
                deviceId: $deviceId,
            );

            $this->jwt->factory()->setTTL($originalTTL);

            $tokens[$role] = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url,
                    'role' => $role,
                ],
                'access_token' => $token,
                'refresh_token' => $refresh->getAttribute('plain_token'),
                'expires_in' => 525600 * 60,
            ];
        }

        return $tokens;
    }
}
