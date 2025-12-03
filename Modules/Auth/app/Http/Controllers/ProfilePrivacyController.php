<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\UpdatePrivacySettingsRequest;
use Modules\Auth\Services\ProfilePrivacyService;

class ProfilePrivacyController extends Controller
{
    public function __construct(
        private ProfilePrivacyService $privacyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->privacyService->getPrivacySettings($user);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(UpdatePrivacySettingsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $settings = $this->privacyService->updatePrivacySettings($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully.',
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
