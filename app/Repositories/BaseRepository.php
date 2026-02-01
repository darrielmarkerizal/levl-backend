<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    abstract protected function model(): string;

    protected array $allowedFilters = [];

    protected array $allowedSorts = ['id', 'created_at', 'updated_at'];

    protected string $defaultSort = 'id';

    protected array $with = [];

    public function applyFiltering(Builder $query, array $params, array $allowedFilters = [], array $allowedSorts = [], string $defaultSort = 'id'): Builder
    {
        return QueryBuilder::for($query)
            ->allowedFilters($allowedFilters ?: $this->allowedFilters)
            ->allowedSorts($allowedSorts ?: $this->allowedSorts)
            ->defaultSort($defaultSort ?: $this->defaultSort)
            ->getSubject();
    }

    public function filteredPaginate(Builder $query, array $params, array $allowedFilters = [], array $allowedSorts = [], string $defaultSort = 'id', int $perPage = 15): LengthAwarePaginator
    {
        return QueryBuilder::for($query)
            ->allowedFilters($allowedFilters ?: $this->allowedFilters)
            ->allowedSorts($allowedSorts ?: $this->allowedSorts)
            ->defaultSort($defaultSort ?: $this->defaultSort)
            ->paginate($perPage);
    }

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
        return QueryBuilder::for($this->model())
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->defaultSort($this->defaultSort)
            ->paginate($perPage);
    }

    public function list(array $params): Collection
    {
        return QueryBuilder::for($this->model())
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->defaultSort($this->defaultSort)
            ->get();
    }

    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    public function getDefaultSort(): string
    {
        return $this->defaultSort;
    }
}
