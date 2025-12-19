<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\ChangePasswordRequest;

/**
 * @tags Profil Pengguna
 */
class ProfilePasswordController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    /**
     * Ubah Kata Sandi Profil
     *
     * Mengubah kata sandi pengguna. Memerlukan password lama untuk verifikasi.
     *
     *
     * @summary Ubah Kata Sandi Profil
     *
     * @response 200 scenario="Success" {"success":true,"message":"Password changed successfully."}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     * @response 422 scenario="Wrong Password" {"success":false,"message":"Password lama tidak cocok."}
     * @response 422 scenario="Validation Error" {"success":false,"message":"Password baru minimal 8 karakter."}
     *
     * @authenticated
     */
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->profileService->changePassword(
            $user,
            $request->input('current_password'),
            $request->input('new_password')
        );

        return $this->success(null, __('messages.auth.password_changed'));
    }
}
