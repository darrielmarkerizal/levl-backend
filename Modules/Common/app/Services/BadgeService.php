<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Meilisearch\Exceptions\ApiException;
use Modules\Common\Repositories\BadgeRepository;
use Modules\Gamification\Models\Badge;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BadgeService
{
    public function __construct(private readonly BadgeRepository $repository) {}

    public function paginate(int $perPage = 15, array $params = []): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        
        $query = Badge::query();

        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            try {
                $ids = Badge::search($searchQuery)->keys()->toArray();

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
                AllowedFilter::partial('code'),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['id', 'code', 'name', 'type', 'threshold', 'created_at', 'updated_at'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }

    public function create(array $data, array $files = []): Badge
    {
        return DB::transaction(function () use ($data, $files) {
            $badge = $this->repository->create($data);

            if (isset($files['icon'])) {
                $badge->addMedia($files['icon'])
                    ->toMediaCollection('icon');
            }

            return $badge->fresh();
        });
    }

    public function find(int $id): ?Badge
    {
        return $this->repository->findById($id);
    }

    public function update(int $id, array $data, array $files = []): ?Badge
    {
        $badge = $this->repository->findById($id);

        if (! $badge) {
            return null;
        }

        return DB::transaction(function () use ($badge, $data, $files) {
            $updated = $this->repository->update($badge, $data);

            if (isset($files['icon'])) {
                $updated->clearMediaCollection('icon');
                $updated->addMedia($files['icon'])
                    ->toMediaCollection('icon');
            }

            return $updated->fresh();
        });
    }

    public function delete(int $id): bool
    {
        $badge = $this->repository->findById($id);

        if (! $badge) {
            return false;
        }

        return DB::transaction(function () use ($badge) {
            $badge->clearMediaCollection('icon');

            return $this->repository->delete($badge);
        });
    }
}
