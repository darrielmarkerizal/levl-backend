<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\DTOs\RegisterDTO;
use Modules\Auth\Events\UserRegistered;
use Modules\Auth\Models\User;
use Modules\Auth\Services\AuthenticationService; 

class RegistrationService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly AuthenticationService $authService, 
    ) {}

    public function registerStudent(RegisterDTO $dto, string $ip, ?string $userAgent): array
    {
        return DB::transaction(function () use ($dto, $ip, $userAgent) {
            $data = $dto->toArray();
            $data['password'] = Hash::make($data['password']);
            
            $user = $this->authRepository->createUser($data);
            $user->assignRole('Student');

            event(new UserRegistered($user));

            
            return $this->authService->generateTokens($user, $ip, $userAgent);
        });
    }

    public function createInstructor(array $validated): User
    {
        return DB::transaction(function () use ($validated) {
            $passwordPlain = Str::password(14, symbols: true);
            $validated['password'] = Hash::make($passwordPlain);
            
            $user = $this->authRepository->createUser($validated);
            $user->assignRole('Instructor');

            event(new UserRegistered($user, $passwordPlain)); 

            return $user;
        });
    }

    public function createAdmin(array $validated): User
    {
        return DB::transaction(function () use ($validated) {
            $passwordPlain = Str::password(14, symbols: true);
            $validated['password'] = Hash::make($passwordPlain);
            
            $user = $this->authRepository->createUser($validated);
            $user->assignRole('Admin');

            event(new UserRegistered($user, $passwordPlain));

            return $user;
        });
    }
}
