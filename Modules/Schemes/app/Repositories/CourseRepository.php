<?php

namespace Modules\Schemes\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface;
use Modules\Schemes\Models\Course;

class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'status',
        'level_tag',
        'type',
        'category_id',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'id',
        'code',
        'title',
        'created_at',
        'updated_at',
        'published_at',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'title';

    /**
     * Default relations to load.
     *
     * @var array<int, string>
     */
    protected array $with = ['tags'];

    protected function model(): string
    {
        return Course::class;
    }

    public function findBySlug(string $slug): ?Course
    {
        return $this->query()->where('slug', $slug)->first();
    }

    /**
     * Paginate courses with optional Scout search and tag filtering.
     *
     * Supports:
     * - filter[search] or search parameter (Meilisearch)
     * - filter[status], filter[level_tag], filter[type], filter[category_id], filter[tag]
     * - sort: id, code, title, created_at, updated_at, published_at
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Course::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Apply tag filters if provided
        $tagFilter = $params['filter']['tag'] ?? request('filter.tag');
        if ($tagFilter) {
            $this->applyTagFilters($query, $tagFilter);
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
     * List all courses with optional Scout search and tag filtering.
     */
    public function list(array $params = []): Collection
    {
        $query = $this->query();

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Course::search($searchQuery)->keys()->toArray();

            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                return new Collection;
            }
        }

        // Apply tag filters if provided
        $tagFilter = $params['filter']['tag'] ?? request('filter.tag');
        if ($tagFilter) {
            $this->applyTagFilters($query, $tagFilter);
        }

        $this->applyFiltering($query, $params, $this->allowedFilters, $this->allowedSorts, $this->defaultSort);

        return $query->get();
    }

    /**
     * Apply tag filters to query.
     */
    private function applyTagFilters(Builder $query, $filterTag): void
    {
        $tags = $this->parseArrayFilter($filterTag);

        if (empty($tags)) {
            return;
        }

        foreach ($tags as $tagValue) {
            $value = trim((string) $tagValue);
            if ($value === '') {
                continue;
            }

            $slug = Str::slug($value);

            $query->whereHas('tags', function (Builder $tagQuery) use ($value, $slug) {
                $tagQuery->where(function (Builder $inner) use ($value, $slug) {
                    $inner->where('slug', $slug)
                        ->orWhere('slug', $value)
                        ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
                });
            });
        }
    }

    /**
     * Parse filter value that may be array or JSON string.
     */
    private function parseArrayFilter($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $trim = trim($value);

            if ($trim === '') {
                return [];
            }

            if ($trim[0] === '[' || str_starts_with($trim, '%5B')) {
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }

                $urldec = urldecode($trim);
                $decoded = json_decode($urldec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }

            return [$trim];
        }

        return [];
    }
}
