<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\EmailVerificationService;
use App\Support\ApiResponse;

class AuthApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth, private readonly EmailVerificationService $emailVerification)
    {
        
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->auth->register(
            validated: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->created($data, 'Registrasi berhasil. Silakan periksa email Anda untuk verifikasi.');
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

        return $this->success($data, 'Login berhasil.');
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

        return $this->success($data, 'Token akses berhasil diperbarui.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['nullable', 'string'],
        ]);

        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $currentJwt = $request->bearerToken();
        if (!$currentJwt) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        $this->auth->logout($user, $currentJwt, $request->input('refresh_token'));

        return $this->success([], 'Logout berhasil.');
    }

    public function profile(): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        return $this->success($user->toArray(), 'Profil berhasil diambil.');
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        /** @var \Modules\Auth\Models\User|null $user */
        $user = auth('api')->user();
        if (!$user) {
            return $this->error('Tidak terotorisasi.', 401);
        }

        if ($user->email_verified_at && $user->status === 'active') {
            return $this->success([], 'Email Anda sudah terverifikasi.');
        }

        $uuid = $this->emailVerification->sendVerificationLink($user);
        if ($uuid === null) {
            return $this->success([], 'Email Anda sudah terverifikasi.');
        }

        return $this->success(['uuid' => $uuid], 'Tautan verifikasi telah dikirim ke email Anda. Berlaku 3 menit dan hanya bisa digunakan sekali.');
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->emailVerification->verifyByCode($request->string('uuid'), $request->string('code'));

        if ($result['status'] === 'ok') {
            return $this->success([], 'Email Anda berhasil diverifikasi.');
        }

        if ($result['status'] === 'expired') {
            return $this->error('Kode verifikasi telah kedaluwarsa.', 422);
        }

        if ($result['status'] === 'invalid') {
            return $this->error('Kode verifikasi salah.', 422);
        }

        if ($result['status'] === 'not_found') {
            return $this->error('Tautan verifikasi tidak ditemukan.', 404);
        }

        return $this->error('Verifikasi gagal.', 422);
    }
}


