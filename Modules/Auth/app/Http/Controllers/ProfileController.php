<?php

namespace Modules\Auth\Http\Controllers;

use App\Contracts\Services\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileServiceInterface $profileService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profileData = $this->profileService->getProfileData($user);

        return response()->json([
            'success' => true,
            'data' => $profileData,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $updatedUser = $this->profileService->updateProfile($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => $this->profileService->getProfileData($updatedUser),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $request->user();
            $avatarUrl = $this->profileService->uploadAvatar($user, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully.',
                'data' => [
                    'avatar_url' => $avatarUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->profileService->deleteAvatar($user);

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
