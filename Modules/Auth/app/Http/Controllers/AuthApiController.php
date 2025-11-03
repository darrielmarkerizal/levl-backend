<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;
use Tymon\JWTAuth\Facades\JWTAuth as JWT;
use Modules\Auth\Http\Responses\ApiResponse;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth)
    {
        
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->auth->register(
            validated: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->created($data, 'Registrasi berhasil');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login');

        try {
            $data = $this->auth->login(
                login: $login,
                password: $request->input('password'),
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );
        } catch (ValidationException $e) {
            return $this->error('Validasi gagal', 422, $e->errors());
        }

        return $this->success($data, 'Login berhasil');
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        try {
            /** @var \Modules\Auth\Models\User $authUser */
            $authUser = auth('api')->user();
            $data = $this->auth->refresh($authUser, $request->string('refresh_token'));
        } catch (ValidationException $e) {
            return $this->error('Refresh token tidak valid atau tidak cocok dengan akun saat ini.', 401);
        }

        return $this->success($data, 'Token berhasil diperbarui');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['nullable', 'string'],
        ]);

        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi: header Authorization Bearer wajib dikirim dan harus valid.', 401);
        }

        $currentJwt = $request->bearerToken();
        if (!$currentJwt) {
            return $this->error('Tidak terotorisasi: token akses tidak ditemukan di header Authorization.', 401);
        }

        $this->auth->logout($user, $currentJwt, $request->input('refresh_token'));

        return $this->success([], 'Logout berhasil');
    }

    public function profile(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi: header Authorization Bearer wajib dikirim dan harus valid.', 401);
        }

        return $this->success($user->toArray(), 'Profil berhasil diambil');
    }
}


