<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTAuth;
use Modules\Auth\Repositories\AuthRepository;
use Modules\Auth\Models\User;
use Modules\Auth\Interfaces\AuthRepositoryInterface;
use Modules\Auth\Interfaces\AuthServiceInterface;
use Modules\Auth\Support\TokenPairDTO;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly JWTAuth $jwt
    ) {}

    public function register(array $validated, string $ip, ?string $userAgent): array
    {
        $validated['password'] = Hash::make($validated['password']);
        $user = $this->authRepository->createUser($validated);

        $token = $this->jwt->fromUser($user);

        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            ttlMinutes: (int) config('jwt.refresh_ttl')
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
        );

        return [ 'user' => $user ] + $pair->toArray();
    }

    public function login(string $login, string $password, string $ip, ?string $userAgent): array
    {
        $user = $this->authRepository->findActiveUserByLogin($login);
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Kredensial tidak valid.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => 'Akun tidak aktif.',
            ]);
        }

        $token = $this->jwt->fromUser($user);

        $refresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $ip,
            userAgent: $userAgent,
            ttlMinutes: (int) config('jwt.refresh_ttl')
        );

        $pair = new TokenPairDTO(
            accessToken: $token,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $refresh->getAttribute('plain_token')
        );

        return [ 'user' => $user ] + $pair->toArray();
    }

    public function refresh(User $currentUser, string $refreshToken): array
    {
        $record = $this->authRepository->findValidRefreshRecordByUser($refreshToken, $currentUser->id);
        if (!$record) {
            throw ValidationException::withMessages([
                'refresh_token' => 'Refresh token tidak valid atau kadaluarsa.',
            ]);
        }

        $user = $record->user;

        $accessToken = $this->jwt->fromUser($user);

        $record->revoke();
        $newRefresh = $this->authRepository->createRefreshToken(
            userId: $user->id,
            ip: $record->ip,
            userAgent: $record->user_agent,
            ttlMinutes: (int) config('jwt.refresh_ttl')
        );

        $pair = new TokenPairDTO(
            accessToken: $accessToken,
            expiresIn: $this->jwt->factory()->getTTL() * 60,
            refreshToken: $newRefresh->getAttribute('plain_token')
        );

        return $pair->toArray();
    }

    public function logout(User $user, string $currentJwt, ?string $refreshToken = null): void
    {
        $this->jwt->setToken($currentJwt)->invalidate(true);

        if ($refreshToken) {
            $this->authRepository->revokeRefreshToken($refreshToken, $user->id);
        } else {
            $this->authRepository->revokeAllUserRefreshTokens($user->id);
        }
    }

    public function me(User $user): User
    {
        return $user;
    }
}


