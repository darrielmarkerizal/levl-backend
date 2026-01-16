<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\DTOs\CreateLessonDTO;
use Modules\Schemes\DTOs\UpdateLessonDTO;
use Modules\Schemes\Models\Lesson;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonService
{
    public function __construct(
        private readonly LessonRepositoryInterface $repository
    ) {}

    public function validateHierarchy(int $courseId, int $unitId, ?int $lessonId = null): void
    {
        if ($lessonId) {
            $lesson = Lesson::with('unit')->findOrFail($lessonId);
            if ((int) $lesson->unit?->course_id !== $courseId || (int) $lesson->unit_id !== $unitId) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.lessons.not_found'));
            }
        } else {
            $unit = Unit::findOrFail($unitId);
            if ((int) $unit->course_id !== $courseId) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(__('messages.units.not_in_course'));
            }
        }
    }

    public function paginate(int $unitId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Lesson::class, new \Illuminate\Http\Request(['filter' => $filters]))
            ->where('unit_id', $unitId)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('status'),
            ])
            ->allowedIncludes(['unit', 'blocks', 'assignments'])
            ->allowedSorts(['order', 'title', 'created_at'])
            ->defaultSort('order');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Lesson
    {
        return $this->repository->findById($id);
    }

    public function getLessonForUser(Lesson $lesson, Course $course, ?\Modules\Auth\Models\User $user): Lesson
    {
        if ($user?->hasRole('Student')) {
            $progression = app(ProgressionService::class);
            $enrollment = $progression->getEnrollmentForCourse($course->id, $user->id);
            if (!$enrollment || !$progression->canAccessLesson($lesson, $enrollment)) {
                throw new \App\Exceptions\BusinessException(__('messages.lessons.locked_prerequisite'), 403);
            }
        }

        return $lesson->load('blocks');
    }

    public function findOrFail(int $id): Lesson
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function create(int $unitId, CreateLessonDTO|array $data): Lesson
    {
        $attributes = $data instanceof CreateLessonDTO ? $data->toArrayWithoutNull() : $data;
        $attributes['unit_id'] = $unitId;

        $attributes = Arr::except($attributes, ['slug']);

        return $this->repository->create($attributes);
    }

    public function update(int $id, UpdateLessonDTO|array $data): Lesson
    {
        $lesson = $this->repository->findByIdOrFail($id);
        $attributes = $data instanceof UpdateLessonDTO ? $data->toArrayWithoutNull() : $data;

        $attributes = Arr::except($attributes, ['slug']);

        return $this->repository->update($lesson, $attributes);
    }

    public function delete(int $id): bool
    {
        $lesson = $this->repository->findByIdOrFail($id);

        return $this->repository->delete($lesson);
    }

    public function publish(int $id): Lesson
    {
        $lesson = $this->repository->findByIdOrFail($id);
        $lesson->update(['status' => 'published']);

        return $lesson->fresh();
    }

    public function unpublish(int $id): Lesson
    {
        $lesson = $this->repository->findByIdOrFail($id);
        $lesson->update(['status' => 'draft']);

        return $lesson->fresh();
    }
}
