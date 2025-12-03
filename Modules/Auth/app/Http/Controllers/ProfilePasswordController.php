<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\ChangePasswordRequest;

class ProfilePasswordController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    public function update(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->profileService->changePassword(
                $user,
                $request->input('current_password'),
                $request->input('new_password')
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
