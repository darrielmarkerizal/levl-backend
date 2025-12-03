<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\UserActivityService;

class ProfileActivityController extends Controller
{
    public function __construct(
        private UserActivityService $activityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'type' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'per_page' => $request->input('per_page', 20),
        ];

        $activities = $this->activityService->getActivities($user, $filters);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }
}
