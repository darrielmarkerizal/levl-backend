<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Common\Models\MasterDataItem;
use Modules\Common\Repositories\MasterDataRepository;
use Modules\Common\Support\MasterDataEnumMapper;

class MasterDataService
{
    private const CACHE_TAG = 'master_data';

    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly MasterDataRepository $repository,
        private readonly MasterDataEnumMapper $enumMapper
    ) {}

    public function get(string $type): array|Collection
    {
        $staticTypes = $this->enumMapper->getStaticTypes();

        if (isset($staticTypes[$type])) {
            return $staticTypes[$type]();
        }

        return Cache::tags([self::CACHE_TAG])->remember(
            "type:{$type}",
            self::CACHE_TTL,
            fn () => $this->repository->allByType($type, ['filter' => ['is_active' => true]])
        );
    }

    public function find(string $type, int $id): ?MasterDataItem
    {
        return $this->repository->find($type, $id);
    }

    public function paginate(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateByType($type, [], $perPage);
    }

    public function isCrudAllowed(string $type): bool
    {
        return ! $this->enumMapper->isStaticType($type);
    }

    public function getAvailableTypes(array $params = []): LengthAwarePaginator
    {
        $staticTypes = $this->buildStaticTypesList();
        $dbTypes = $this->buildDatabaseTypesList();
        $merged = $staticTypes->concat($dbTypes);

        $merged = $this->applyTypeFilters($merged, $params);
        $merged = $this->applySorting($merged, $params);

        return $this->paginateCollection($merged, $params);
    }

    public function create(string $type, array $data): MasterDataItem
    {
        $data['type'] = $type;

        return $this->repository->create($data);
    }

    public function update(string $type, int $id, array $data): MasterDataItem
    {
        $item = $this->repository->find($type, $id);

        if (! $item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Master data item not found.');
        }

        return $this->repository->update($item, $data);
    }

    public function delete(string $type, int $id): bool
    {
        $item = $this->repository->find($type, $id);

        if (! $item) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Master data item not found.');
        }

        return $this->repository->delete($item);
    }

    private function buildStaticTypesList(): Collection
    {
        $staticTypes = $this->enumMapper->getStaticTypes();

        return collect(array_keys($staticTypes))->map(function ($key) use ($staticTypes) {
            $data = $staticTypes[$key]();
            $count = is_array($data) ? count($data) : $data->count();

            return [
                'type' => $key,
                'label' => __("messages.master_data.{$key}") ?? ucwords(str_replace('-', ' ', $key)),
                'is_crud' => false,
                'count' => $count,
                'last_updated' => null,
            ];
        });
    }

    private function buildDatabaseTypesList(): Collection
    {
        return $this->repository->getTypes()->map(function ($item) {
            $item = is_array($item) ? $item : $item->toArray();

            if (isset($item['key']) && ! isset($item['type'])) {
                $item['type'] = $item['key'];
                unset($item['key']);
            }

            return $item;
        });
    }

    private function applyTypeFilters(Collection $merged, array $params): Collection
    {
        $merged = $this->filterByCrud($merged, $params);
        $merged = $this->filterBySearch($merged, $params);

        return $merged;
    }

    private function filterByCrud(Collection $merged, array $params): Collection
    {
        if (! isset($params['filter']['is_crud'])) {
            return $merged;
        }

        $isCrud = filter_var($params['filter']['is_crud'], FILTER_VALIDATE_BOOLEAN);
        return $merged->filter(fn ($item) => $item['is_crud'] === $isCrud);
    }

    private function filterBySearch(Collection $merged, array $params): Collection
    {
        if (empty($params['search'])) {
            return $merged;
        }

        $search = strtolower($params['search']);
        return $merged->filter(
            fn ($item) => str_contains(strtolower($item['type']), $search) || 
                         str_contains(strtolower($item['label']), $search)
        );
    }

    private function applySorting(Collection $merged, array $params): Collection
    {
        $validSorts = $this->getValidSortsFromParams($params);

        return $this->applySortsToCollection($merged, $validSorts);
    }

    private function getValidSortsFromParams(array $params): array
    {
        $allowedSorts = ['type', 'label', 'count', 'last_updated'];
        $defaultSort = 'label';
        $sortParam = $params['sort'] ?? $defaultSort;
        $requestedSorts = is_array($sortParam) ? $sortParam : explode(',', (string) $sortParam);

        return $this->extractValidSorts($requestedSorts, $allowedSorts, $defaultSort);
    }

    private function applySortsToCollection(Collection $merged, array $validSorts): Collection
    {
        foreach (array_reverse($validSorts) as $sort) {
            $merged = $this->applySingleSort($merged, $sort);
        }

        return $merged;
    }

    private function applySingleSort(Collection $collection, string $sort): Collection
    {
        $descending = str_starts_with($sort, '-');
        $field = $descending ? substr($sort, 1) : $sort;

        return $descending
            ? $collection->sortByDesc($field, SORT_NATURAL | SORT_FLAG_CASE)
            : $collection->sortBy($field, SORT_NATURAL | SORT_FLAG_CASE);
    }

    private function extractValidSorts(array $requestedSorts, array $allowedSorts, string $defaultSort): array
    {
        $validSorts = array_filter(
            array_map(fn($sort) => $this->normalizeSortIfValid(trim($sort), $allowedSorts), $requestedSorts)
        );

        return empty($validSorts) ? [$defaultSort] : $validSorts;
    }

    private function normalizeSortIfValid(string $sort, array $allowedSorts): ?string
    {
        $descending = str_starts_with($sort, '-');
        $field = $descending ? substr($sort, 1) : $sort;

        if (! in_array($field, $allowedSorts, true)) {
            return null;
        }

        return $descending ? "-{$field}" : $field;
    }

    private function paginateCollection(Collection $merged, array $params): LengthAwarePaginator
    {
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Support\Facades\Request::url(), 'query' => $params]
        );
    }

    public function extractQueryParams(array $query): array
    {
        return [
            'search' => $query['search'] ?? null,
            'sort' => $query['sort'] ?? null,
            'sort_order' => $query['sort_order'] ?? null,
            'page' => $query['page'] ?? null,
            'per_page' => $query['per_page'] ?? null,
            'filter' => $query['filter'] ?? null,
        ];
    }

    public function getValidationRules(bool $isUpdate = false): array
    {
        return [
            'value' => ($isUpdate ? 'sometimes|' : '').'required|string|max:255',
            'label' => ($isUpdate ? 'sometimes|' : '').'required|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'nullable|array',
        ];
    }
}
