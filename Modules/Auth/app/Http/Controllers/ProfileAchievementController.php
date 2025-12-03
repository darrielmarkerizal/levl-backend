<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\PinnedBadge;

class ProfileAchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $badges = $user->badges()->with('badge')->get();
        $pinnedBadges = $user->pinnedBadges()->with('badge')->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'badges' => $badges,
                'pinned_badges' => $pinnedBadges,
            ],
        ]);
    }

    public function pinBadge(Request $request, int $badgeId): JsonResponse
    {
        $request->validate([
            'order' => 'sometimes|integer|min:0',
        ]);

        try {
            $user = $request->user();

            // Check if user has this badge
            $hasBadge = $user->badges()->where('badge_id', $badgeId)->exists();
            if (! $hasBadge) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have this badge.',
                ], 404);
            }

            // Check if already pinned
            $existing = PinnedBadge::where('user_id', $user->id)
                ->where('badge_id', $badgeId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Badge is already pinned.',
                ], 422);
            }

            // Pin the badge
            $pinnedBadge = PinnedBadge::create([
                'user_id' => $user->id,
                'badge_id' => $badgeId,
                'order' => $request->input('order', 0),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Badge pinned successfully.',
                'data' => $pinnedBadge->load('badge'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function unpinBadge(Request $request, int $badgeId): JsonResponse
    {
        try {
            $user = $request->user();

            $pinnedBadge = PinnedBadge::where('user_id', $user->id)
                ->where('badge_id', $badgeId)
                ->first();

            if (! $pinnedBadge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Badge is not pinned.',
                ], 404);
            }

            $pinnedBadge->delete();

            return response()->json([
                'success' => true,
                'message' => 'Badge unpinned successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
