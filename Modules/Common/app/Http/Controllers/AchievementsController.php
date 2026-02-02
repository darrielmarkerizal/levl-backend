<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use App\Support\Traits\ProvidesMetadata;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Http\Requests\AchievementStoreRequest;
use Modules\Common\Http\Requests\AchievementUpdateRequest;
use Modules\Common\Http\Resources\AchievementResource;
use Modules\Common\Services\AchievementService;
use Modules\Gamification\Models\Challenge;

class AchievementsController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
    use HandlesFiltering;
    use ProvidesMetadata;

    public function __construct(private readonly AchievementService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Challenge::class);
        $params = $this->extractFilterParams($request);
        $perPage = $params['per_page'] ?? 15;
        $paginator = $this->service->paginate($perPage, $params);
        $paginator->getCollection()->transform(fn ($item) => new AchievementResource($item));

        return $this->paginateResponse($paginator);
    }

    public function store(AchievementStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Challenge::class);
        $achievement = $this->service->create($request->validated());

        return $this->created(new AchievementResource($achievement), __('messages.achievements.created'));
    }

    public function show(int $achievement): JsonResponse
    {
        $model = $this->service->find($achievement);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('view', $model);

        return $this->success(new AchievementResource($model));
    }

    public function update(AchievementUpdateRequest $request, int $achievement): JsonResponse
    {
        $model = $this->service->find($achievement);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('update', $model);

        $updated = $this->service->update($achievement, $request->validated());

        return $this->success(new AchievementResource($updated), __('messages.achievements.updated'));
    }

    public function destroy(int $achievement): JsonResponse
    {
        $model = $this->service->find($achievement);

        if (! $model) {
            return $this->error(__('messages.achievements.not_found'), 404);
        }

        $this->authorize('delete', $model);

        $this->service->delete($achievement);

        return $this->success([], __('messages.achievements.deleted'));
    }
}
