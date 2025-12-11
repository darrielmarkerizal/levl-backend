<?php

namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use App\Support\FilterableRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use FilterableRepository;

    /**
     * Get the model class name.
     */
    abstract protected function model(): string;

    /**
     * Allowed filter keys.
     *
     * @var array<int, string>
     */
    protected array $allowedFilters = [];

    /**
     * Allowed sort fields.
     *
     * @var array<int, string>
     */
    protected array $allowedSorts = ['id', 'created_at', 'updated_at'];

    /**
     * Default sort field.
     */
    protected string $defaultSort = 'id';

    /**
     * Default relations to load.
     *
     * @var array<int, string>
     */
    protected array $with = [];

    public function query(): Builder
    {
        return $this->model()::query()->with($this->with);
    }

    public function findById(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findByIdOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->model()::create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function paginate(array $params, int $perPage = 15): LengthAwarePaginator
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

    public function list(array $params): Collection
    {
        $query = $this->query();
        $this->applyFiltering($query, $params, $this->allowedFilters, $this->allowedSorts, $this->defaultSort);

        return $query->get();
    }

    /**
     * Check if this repository supports filtering.
     */
    public function supportsFiltering(): bool
    {
        return ! empty($this->allowedFilters);
    }

    /**
     * Check if this repository supports sorting.
     */
    public function supportsSorting(): bool
    {
        return ! empty($this->allowedSorts);
    }

    /**
     * Get allowed filters for this repository.
     *
     * @return array<int, string>
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    /**
     * Get allowed sorts for this repository.
     *
     * @return array<int, string>
     */
    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * Get default sort for this repository.
     */
    public function getDefaultSort(): string
    {
        return $this->defaultSort;
    }
}
