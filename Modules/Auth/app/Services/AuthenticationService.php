<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\Services\AuthenticationServiceInterface;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\Enums\UserStatus;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Models\User;
use Modules\Auth\Support\TokenPairDTO;
use Tymon\JWTAuth\JWTAuth;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt,
        private readonly LoginThrottlingService $throttle,
    ) {}

    public function login(
        LoginDTO|string $loginOrDto,
        ?string $password,
        string $ip,
        ?string $userAgent
    ): array {
        if ($loginOrDto instanceof LoginDTO) {
            $login = $loginOrDto->login;
            $password = $loginOrDto->password;
        } else {
            $login = $loginOrDto;
        }

        $this->throttle->ensureNotLocked($login);

        if ($this->throttle->tooManyAttempts($login, $ip)) {
            $this->throwThrottledException($login, $ip);
        }

        $user = $this->authRepository->findByLogin($login);

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->throttle->hitAttempt($login, $ip);
            $this->throttle->recordFailureAndMaybeLock($login);
            throw new BusinessException(
                __('messages.auth.invalid_credentials'),
                ['login' => [__('messages.auth.invalid_credentials')]],
                401
            );
        }

        $wasAutoVerified = $this->autoVerifyPrivilegedUser($user);

        return DB::transaction(function () use ($user, $ip, $userAgent, $login, $wasAutoVerified) {
            $this->throttle->clearAttempts($login, $ip);

            $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            event(new UserLoggedIn($user, $ip, $userAgent, $loginType));

            return $this->generateTokens($user, $ip, $userAgent, $wasAutoVerified);
        });
    }

    public function generateTokens(User $user, string $ip, ?string $userAgent, bool $wasAutoVerified = false): array
    {
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

        $userArray = $user->toArray();
        $userArray['roles'] = $user->getRoleNames()->values();

        $response = ['user' => $userArray] + $pair->toArray();
        if ($wasAutoVerified) {
            $response['message'] = __('messages.auth.login_success_auto_verified');
        }

        return $response;
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        DB::transaction(function () use ($user, $currentJwt, $refreshToken) {
            $this->jwt->setToken($currentJwt)->invalidate();

            if ($refreshToken) {
                $this->authRepository->revokeRefreshToken($refreshToken, $user->id);
            } else {
                $this->authRepository->revokeAllUserRefreshTokens($user->id);
            }

            activity('auth')
                ->causedBy($user)
                ->withProperties(['action' => 'logout'])
                ->log(__('messages.auth.log_user_logout'));
        });
    }

    public function refresh(string $refreshToken, string $ip, ?string $userAgent): array
    {
        return DB::transaction(function () use ($refreshToken, $ip, $userAgent) {
            $record = $this->authRepository->findValidRefreshRecord($refreshToken);
            if (! $record) {
                throw ValidationException::withMessages(['refresh_token' => __('messages.auth.refresh_token_invalid')]);
            }

            $user = $record->user;
            if (! $user || $user->status !== UserStatus::Active) {
                throw ValidationException::withMessages(['refresh_token' => __('messages.auth.account_not_active')]);
            }

            if ($record->isReplaced()) {
                $chain = $this->authRepository->findReplacedTokenChain($record->id);
                $deviceIds = collect($chain)->pluck('device_id')->unique()->filter()->toArray();
                foreach ($deviceIds as $deviceId) {
                    $this->authRepository->revokeAllUserRefreshTokensByDevice($user->id, $deviceId);
                }
                throw ValidationException::withMessages(['refresh_token' => __('messages.auth.refresh_token_compromised')]);
            }

            $deviceId = $record->device_id ?? hash('sha256', ($ip ?? '').($userAgent ?? '').$user->id);
            $newRefresh = $this->authRepository->createRefreshToken(
                userId: $user->id,
                ip: $ip,
                userAgent: $userAgent,
                deviceId: $deviceId
            );

            $this->authRepository->markTokenAsReplaced($record->id, $newRefresh->id);
            $record->update(['last_used_at' => now(), 'idle_expires_at' => now()->addDays(14)]);

            return [
                'access_token' => $this->jwt->fromUser($user),
                'expires_in' => $this->jwt->factory()->getTTL() * 60,
                'refresh_token' => $newRefresh->getAttribute('plain_token'),
            ];
        });
    }

    private function autoVerifyPrivilegedUser(User $user): bool
    {
        $roles = $user->getRoleNames();
        $isPrivileged = $roles->intersect(['Superadmin', 'Admin', 'Instructor'])->isNotEmpty();

        if ($isPrivileged && ($user->status === UserStatus::Pending || $user->email_verified_at === null)) {
            $user->email_verified_at = now();
            $user->status = UserStatus::Active;
            $user->save();

            return true;
        }

        return false;
    }

    private function throwThrottledException(string $login, string $ip): never
    {
        $retryAfter = $this->throttle->getRetryAfterSeconds($login, $ip);
        $cfg = $this->throttle->getRateLimitConfig();
        $s = $retryAfter % 60;
        $m = intdiv($retryAfter, 60);
        $retryIn = $m > 0 ? $m.' menit'.($s > 0 ? ' '.$s.' detik' : '') : $s.' detik';

        throw ValidationException::withMessages([
            'login' => __('messages.auth.throttle_message', ['max' => $cfg['max'], 'decay' => $cfg['decay'], 'retryIn' => $retryIn]),
        ]);
    }

    public function getJwt(): JWTAuth
    {
        return $this->jwt;
    }
}
