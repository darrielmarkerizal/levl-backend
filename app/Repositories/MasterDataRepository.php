<?php

namespace App\Repositories;

use App\Models\MasterDataItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MasterDataRepository extends BaseRepository
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'is_active',
        'is_system',
        'value',
        'label',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'value',
        'label',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'sort_order';

    protected function model(): string
    {
        return MasterDataItem::class;
    }

    /**
     * Get paginated master data by type with optional Scout search.
     *
     * Supports:
     * - filter[is_active], filter[is_system], filter[value], filter[label]
     * - filter[search] or search parameter for Scout/Meilisearch
     * - sort: value, label, sort_order, created_at, updated_at (prefix with - for desc)
     */
    public function paginateByType(string $type, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->where('type', $type);

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = MasterDataItem::search($searchQuery)
                ->query(fn ($q) => $q->where('type', $type))
                ->keys()
                ->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $this->filteredPaginate(
            $query,
            $params,
            $this->allowedFilters,
            $this->allowedSorts,
            $this->defaultSort,
            $perPage
        );
    }

    /**
     * Get all master data by type (no pagination) with optional Scout search.
     */
    public function allByType(string $type, array $params = []): Collection
    {
        $query = $this->query()->where('type', $type);

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = MasterDataItem::search($searchQuery)
                ->query(fn ($q) => $q->where('type', $type))
                ->keys()
                ->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                return new Collection;
            }
        }

        $this->applyFiltering($query, $params, $this->allowedFilters, $this->allowedSorts, $this->defaultSort);

        return $query->get();
    }

    /**
     * Get all distinct types.
     */
    public function getTypes(): Collection
    {
        return MasterDataItem::select('type')
            ->distinct()
            ->orderBy('type')
            ->get();
    }

    /**
     * Find by ID within a type.
     */
    public function find(string $type, int $id): ?MasterDataItem
    {
        return MasterDataItem::where('type', $type)
            ->where('id', $id)
            ->first();
    }

    /**
     * Check if value exists in type.
     */
    public function valueExists(string $type, string $value, ?int $excludeId = null): bool
    {
        return MasterDataItem::where('type', $type)
            ->where('value', $value)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
