<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Enums\UserStatus;
use Symfony\Component\HttpFoundation\Response;

class AllowExpiredToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/v1/auth/refresh') && ! $request->routeIs('auth.refresh')) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Middleware ini hanya untuk endpoint refresh.',
                ],
                403,
            );
        }

        $refreshToken =
          $request->cookie('refresh_token') ??
          ($request->header('X-Refresh-Token') ?? $request->input('refresh_token'));

        if (empty($refreshToken)) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Refresh token diperlukan.',
                ],
                400,
            );
        }

        $authRepository = app(AuthRepositoryInterface::class);
        $refreshRecord = $authRepository->findValidRefreshRecord($refreshToken);

        if (! $refreshRecord) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Refresh token tidak valid atau kadaluarsa.',
                ],
                401,
            );
        }

        $user = $refreshRecord->user;
        if (! $user) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'User tidak ditemukan.',
                ],
                401,
            );
        }

        // Pastikan hanya user dengan status aktif yang bisa refresh token.
        // Kolom `status` di-cast ke enum `UserStatus`, jadi harus dibandingkan dengan enum, bukan string.
        if ($user->status !== UserStatus::Active) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Akun tidak aktif.',
                ],
                403,
            );
        }

        auth('api')->setUser($user);
        $request->merge(['refresh_token' => $refreshToken]);

        return $next($request);
    }
}
