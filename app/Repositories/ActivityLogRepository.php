<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogRepository extends BaseRepository
{
    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [
        'log_name',
        'event',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
    ];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = [
        'id',
        'created_at',
        'event',
        'log_name',
    ];

    /**
     * Default sort field.
     */
    protected string $defaultSort = '-created_at';

    /**
     * Default relations to load.
     *
     * @var array<int, string>
     */
    protected array $with = ['causer', 'subject'];

    protected function model(): string
    {
        return Activity::class;
    }

    /**
     * Get paginated activity logs.
     *
     * Supports:
     * - filter[log_name], filter[event], filter[subject_type], filter[subject_id]
     * - filter[causer_type], filter[causer_id]
     * - sort: id, created_at, event, log_name (prefix with - for desc)
     */
    public function paginate(array $params = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredPaginate(
            $this->query(),
            $params,
            $this->allowedFilters,
            $this->allowedSorts,
            $this->defaultSort,
            $perPage
        );
    }

    /**
     * Find activity log by ID.
     */
    public function find(int $id): ?Activity
    {
        return $this->query()->find($id);
    }
}
