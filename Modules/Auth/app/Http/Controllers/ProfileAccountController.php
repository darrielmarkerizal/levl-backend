<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\DeleteAccountRequest;

class ProfileAccountController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->profileService->deleteAccount($user, $request->input('password'));

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully. You have 30 days to restore it.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function restore(DeleteAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->profileService->restoreAccount($user);

            return response()->json([
                'success' => true,
                'message' => 'Account restored successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
