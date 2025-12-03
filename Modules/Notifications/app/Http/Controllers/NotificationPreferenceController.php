<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Contracts\NotificationPreferenceServiceInterface;
use Modules\Notifications\Models\NotificationPreference;

class NotificationPreferenceController extends Controller
{
    protected NotificationPreferenceServiceInterface $preferenceService;

    public function __construct(NotificationPreferenceServiceInterface $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * Get user's notification preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = $this->preferenceService->getPreferences(auth()->user());

        return response()->json([
            'success' => true,
            'data' => $preferences,
            'meta' => [
                'categories' => NotificationPreference::getCategories(),
                'channels' => NotificationPreference::getChannels(),
                'frequencies' => NotificationPreference::getFrequencies(),
            ],
        ]);
    }

    /**
     * Update user's notification preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.category' => 'required|string|in:'.implode(',', NotificationPreference::getCategories()),
            'preferences.*.channel' => 'required|string|in:'.implode(',', NotificationPreference::getChannels()),
            'preferences.*.enabled' => 'required|boolean',
            'preferences.*.frequency' => 'required|string|in:'.implode(',', NotificationPreference::getFrequencies()),
        ]);

        $success = $this->preferenceService->updatePreferences(
            auth()->user(),
            $validated['preferences']
        );

        if ($success) {
            $preferences = $this->preferenceService->getPreferences(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => $preferences,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update notification preferences',
        ], 500);
    }

    /**
     * Reset user's notification preferences to defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        $success = $this->preferenceService->resetToDefaults(auth()->user());

        if ($success) {
            $preferences = $this->preferenceService->getPreferences(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences reset to defaults successfully',
                'data' => $preferences,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset notification preferences',
        ], 500);
    }
}
