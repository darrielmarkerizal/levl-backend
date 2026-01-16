<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\UserActivityService;

class ProfileActivityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserActivityService $activityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'type' => $request->input('filter.type'),
            'start_date' => $request->input('filter.start_date'),
            'end_date' => $request->input('filter.end_date'),
            'per_page' => $request->input('per_page', 20),
        ];

        $paginator = $this->activityService->getActivities($user, $filters);
        $paginator->getCollection()->transform(fn($activity) => new \Modules\Auth\Http\Resources\UserActivityResource($activity));

        return $this->paginateResponse($paginator);
    }
}
