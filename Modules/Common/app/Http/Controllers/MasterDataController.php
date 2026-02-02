<?php

declare(strict_types=1);

namespace Modules\Common\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Common\Http\Resources\MasterDataTypeResource;
use Modules\Common\Services\MasterDataService;

class MasterDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MasterDataService $service
    ) {}

    public function types(Request $request): JsonResponse
    {
        $params = $this->service->extractQueryParams($request->query->all());
        $paginator = $this->service->getAvailableTypes($params);
        $paginator->getCollection()->transform(fn ($item) => new MasterDataTypeResource($item));

        return $this->paginateResponse($paginator, __('messages.master_data.types_retrieved'));
    }

    public function get(string $type): JsonResponse
    {
        $data = $this->service->get($type);

        return $this->success($data, __('messages.master_data.retrieved'));
    }

    public function index(Request $request, string $type): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->paginate($type, $perPage);

        return $this->paginateResponse($paginator, __('messages.master_data.retrieved'));
    }

    public function show(string $type, int $id): JsonResponse
    {
        $item = $this->service->find($type, $id);

        if (! $item) {
            return $this->error(__('messages.master_data.not_found'), 404);
        }

        return $this->success($item, __('messages.master_data.retrieved'));
    }

    public function store(Request $request, string $type): JsonResponse
    {
        if (! $this->service->isCrudAllowed($type)) {
            return $this->forbidden(__('messages.master_data.crud_not_allowed'));
        }

        $data = $request->validate($this->service->getValidationRules());
        $item = $this->service->create($type, $data);

        return $this->success($item, __('messages.master_data.created'));
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if (! $this->service->isCrudAllowed($type)) {
            return $this->forbidden(__('messages.master_data.crud_not_allowed'));
        }

        $data = $request->validate($this->service->getValidationRules(true));
        $item = $this->service->update($type, $id, $data);

        return $this->success($item, __('messages.master_data.updated'));
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        if (! $this->service->isCrudAllowed($type)) {
            return $this->forbidden(__('messages.master_data.crud_not_allowed'));
        }

        $this->service->delete($type, $id);

        return $this->success(null, __('messages.master_data.deleted'));
    }
}

