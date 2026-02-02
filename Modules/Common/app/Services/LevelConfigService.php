<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Meilisearch\Exceptions\ApiException;
use Modules\Common\Models\LevelConfig;
use Modules\Common\Repositories\LevelConfigRepository;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LevelConfigService
{
    public function __construct(private readonly LevelConfigRepository $repository) {}

    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = LevelConfig::query();

        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            try {
                $ids = LevelConfig::search($searchQuery)->keys()->toArray();

                if (! empty($ids)) {
                    $query->whereIn('id', $ids);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } catch (ApiException $e) {
                if (str_contains($e->getMessage(), 'not found')) {
                    $query->whereRaw('1 = 0');
                } else {
                    throw $e;
                }
            }
        }

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('level'),
                AllowedFilter::partial('name'),
            ])
            ->allowedSorts(['id', 'level', 'name', 'xp_required', 'created_at', 'updated_at'])
            ->defaultSort('level')
            ->paginate($perPage);
    }

    public function create(array $data): LevelConfig
    {
        return $this->repository->create($data);
    }

    public function find(int $id): ?LevelConfig
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data): ?LevelConfig
    {
        $config = $this->repository->findById($id);

        if (! $config) {
            return null;
        }

        return $this->repository->update($config, $data);
    }

    public function delete(int $id): bool
    {
        $config = $this->repository->findById($id);

        if (! $levelConfig) {
            return false;
        }

        return $this->repository->delete($levelConfig);
    }
}
