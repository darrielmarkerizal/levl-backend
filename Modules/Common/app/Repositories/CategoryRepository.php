<?php

namespace Modules\Common\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Common\Models\Category;

class CategoryRepository extends BaseRepository
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'name',
        'value',
        'description',
        'status',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'name',
        'value',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = '-created_at';

    protected function model(): string
    {
        return Category::class;
    }

    /**
     * Get paginated categories with optional Scout search.
     *
     * Supports:
     * - filter[name], filter[value], filter[description], filter[status]
     * - filter[search] or search parameter for Scout/Meilisearch full-text search
     * - sort: name, value, status, created_at, updated_at (prefix with - for desc)
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Category::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                // No results from search, return empty paginator
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
     * Get all categories (no pagination) with optional Scout search.
     */
    public function all(array $params = []): Collection
    {
        $query = $this->query();

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Category::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                // No results from search, return empty collection
                return new Collection;
            }
        }

        $this->applyFiltering($query, $params, $this->allowedFilters, $this->allowedSorts, $this->defaultSort);

        return $query->get();
    }

    public function findById(int $id): ?Category
    {
        return $this->query()->find($id);
    }

    public function findByIdOrFail(int $id): Category
    {
        return $this->query()->findOrFail($id);
    }
}
