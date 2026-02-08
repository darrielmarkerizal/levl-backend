<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\MentionUserSearchRequest;
use Modules\Auth\Http\Resources\MentionUserResource;
use Modules\Auth\Services\MentionUserSearchService;
use Modules\Schemes\Models\Course;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MentionUserSearchService $mentionUserSearchService
    ) {}

    public function searchMentions(MentionUserSearchRequest $request, Course $course): JsonResponse
    {
        $validated = $request->validated();
        $results = $this->mentionUserSearchService->search(
            $course,
            $validated['search'],
            (int) ($validated['limit'] ?? 10)
        );

        return $this->success(MentionUserResource::collection($results));
    }
}
