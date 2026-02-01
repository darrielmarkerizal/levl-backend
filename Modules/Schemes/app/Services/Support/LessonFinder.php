<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Services\ProgressionService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LessonFinder
{
    use \App\Support\Traits\BuildsQueryBuilderRequest;

    public function __construct(
        private readonly LessonRepositoryInterface $repository
    ) {}

    public function paginate(int $unitId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        $query = QueryBuilder::for(Lesson::class, $this->buildQueryBuilderRequest($filters))
            ->where('unit_id', $unitId)
            ->allowedFilters([
                AllowedFilter::exact('content_type'),
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

    public function findOrFail(int $id): Lesson
    {
        return $this->repository->findByIdOrFail($id);
    }

    public function getLessonForUser(Lesson $lesson, Course $course, ?User $user): Lesson
    {
        if ($user?->hasRole('Student')) {
            $progression = app(ProgressionService::class);
            $enrollment = $progression->getEnrollmentForCourse($course->id, $user->id);
            if (! $enrollment || ! $progression->canAccessLesson($lesson, $enrollment)) {
                throw new \App\Exceptions\BusinessException(__('messages.lessons.locked_prerequisite'), 403);
            }
        }

        return $lesson->load('blocks');
    }
}
