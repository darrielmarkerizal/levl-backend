<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use App\Support\CodeGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Schemes\Contracts\Repositories\UnitRepositoryInterface;
use Modules\Schemes\DTOs\CreateUnitDTO;
use Modules\Schemes\DTOs\UpdateUnitDTO;
use Modules\Schemes\Models\Unit;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UnitService
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function __construct(
        private readonly UnitRepositoryInterface $repository,
        private readonly SchemesCacheService $cacheService
    ) {}

    public function validateHierarchy(int $courseId, int $unitId): void
    {
        $unit = Unit::findOrFail($unitId);

        if ((int) $unit->course_id !== $courseId) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.units.not_in_course'));
        }
    }

    public function paginate(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Unit::class, $this->buildQueryBuilderRequest($filters))
            ->where('course_id', $courseId)
            ->allowedFilters([
                AllowedFilter::exact('status'),
            ])
            ->allowedIncludes(['course', 'lessons'])
            ->allowedSorts(['order', 'title', 'created_at'])
            ->defaultSort('order');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Unit
    {
        return $this->repository->findById($id);
    }

    public function findOrFail(int $id): Unit
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(int $courseId, CreateUnitDTO|array $data): Unit
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($courseId, $data) {
            $attributes = $data instanceof CreateUnitDTO ? $data->toArrayWithoutNull() : $data;
            $attributes['course_id'] = $courseId;

            if (empty($attributes['code'])) {
                $attributes['code'] = CodeGenerator::generate('UNIT-', 4, Unit::class);
            }

            if (isset($attributes['order'])) {
                Unit::where('course_id', $courseId)
                    ->where('order', '>=', $attributes['order'])
                    ->increment('order');
            } else {
                $maxOrder = Unit::where('course_id', $courseId)->max('order');
                $attributes['order'] = $maxOrder ? $maxOrder + 1 : 1;
            }

            $attributes = Arr::except($attributes, ['slug']);

            return $this->repository->create($attributes);
        });
    }

    public function update(int $id, UpdateUnitDTO|array $data): Unit
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $unit = $this->repository->findByIdOrFail($id);
            $attributes = $data instanceof UpdateUnitDTO ? $data->toArrayWithoutNull() : $data;

            if (isset($attributes['order']) && $attributes['order'] != $unit->order) {
                $newOrder = $attributes['order'];
                $currentOrder = $unit->order;
                $courseId = $unit->course_id;

                if ($newOrder < $currentOrder) {

                    Unit::where('course_id', $courseId)
                        ->where('order', '>=', $newOrder)
                        ->where('order', '<', $currentOrder)
                        ->increment('order');
                } elseif ($newOrder > $currentOrder) {

                    Unit::where('course_id', $courseId)
                        ->where('order', '>', $currentOrder)
                        ->where('order', '<=', $newOrder)
                        ->decrement('order');
                }
            }

            $attributes = Arr::except($attributes, ['slug']);

            return $this->repository->update($unit, $attributes);
        });
    }

    public function delete(int $id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $unit = $this->repository->findByIdOrFail($id);
            $courseId = $unit->course_id;
            $deletedOrder = $unit->order;

            $deleted = $this->repository->delete($unit);

            if ($deleted) {

                Unit::where('course_id', $courseId)
                    ->where('order', '>', $deletedOrder)
                    ->decrement('order');
            }

            return $deleted;
        });
    }

    public function reorder(int $courseId, array $data): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($courseId, $data) {
            $unitIds = array_map('intval', $data['units']);

            if (count($unitIds) !== count(array_unique($unitIds))) {
                throw new \InvalidArgumentException(__('messages.units.duplicate_ids'));
            }

            $allUnits = Unit::where('course_id', $courseId)->pluck('id')->toArray();

            if (count($unitIds) !== count($allUnits) || array_diff($allUnits, $unitIds)) {
                throw new \InvalidArgumentException(__('messages.units.must_include_all'));
            }

            $count = Unit::whereIn('id', $unitIds)
                ->where('course_id', $courseId)
                ->count();

            if ($count !== count($unitIds)) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.units.some_not_found'));
            }

            foreach ($unitIds as $index => $unitId) {
                $this->repository->updateOrder($unitId, $index + 1);
            }

            $this->cacheService->invalidateCourse($courseId);

            return true;
        });
    }

    public function publish(int $id): Unit
    {
        $unit = $this->repository->findByIdOrFail($id);
        $unit->update(['status' => 'published']);

        $this->cacheService->invalidateCourse($unit->course_id);

        return $unit->fresh();
    }

    public function unpublish(int $id): Unit
    {
        $unit = $this->repository->findByIdOrFail($id);
        $unit->update(['status' => 'draft']);

        $this->cacheService->invalidateCourse($unit->course_id);

        return $unit->fresh();
    }
}
