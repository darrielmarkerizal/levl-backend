<?php

namespace Modules\Schemes\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\Models\Lesson;

class LessonRepository extends BaseRepository implements LessonRepositoryInterface
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'status',
        'content_type',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'id',
        'title',
        'order',
        'status',
        'duration_minutes',
        'created_at',
        'updated_at',
        'published_at',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'order';

    protected function model(): string
    {
        return Lesson::class;
    }

    /**
     * Find lessons by unit with optional Scout search.
     *
     * Supports:
     * - filter[status], filter[content_type], filter[search] or search parameter (Meilisearch)
     * - sort: id, title, order, status, duration_minutes, created_at, updated_at, published_at (prefix with - for desc)
     */
    public function findByUnit(int $unitId, array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->where('unit_id', $unitId);

        // Handle Scout search if search parameter is provided
        $searchQuery = $params['search'] ?? request('filter.search') ?? request('search');

        if ($searchQuery && trim($searchQuery) !== '') {
            $ids = Lesson::search($searchQuery)
                ->query(fn ($q) => $q->where('unit_id', $unitId))
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

    public function findByUnitAndId(int $unitId, int $id): ?Lesson
    {
        return $this->query()->where('unit_id', $unitId)->find($id);
    }

    public function getMaxOrderForUnit(int $unitId): int
    {
        return (int) Lesson::where('unit_id', $unitId)->max('order') ?? 0;
    }

    public function getAllByUnit(int $unitId): Collection
    {
        return $this->query()
            ->where('unit_id', $unitId)
            ->orderBy('order', 'asc')
            ->get();
    }
}
