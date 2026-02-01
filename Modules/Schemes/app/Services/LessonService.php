<?php

declare(strict_types=1);

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface;
use Modules\Schemes\Contracts\Services\LessonServiceInterface;
use Modules\Schemes\DTOs\CreateLessonDTO;
use Modules\Schemes\DTOs\UpdateLessonDTO;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Services\Support\LessonFinder;
use Modules\Schemes\Services\Support\LessonOrderingProcessor;

class LessonService implements LessonServiceInterface
{
    public function __construct(
        private readonly LessonFinder $finder,
        private readonly LessonOrderingProcessor $orderingProcessor
    ) {}

    public function validateHierarchy(int $courseId, int $unitId, ?int $lessonId = null): void
    {
        $this->orderingProcessor->validateHierarchy($courseId, $unitId, $lessonId);
    }

    public function paginate(int $unitId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->finder->paginate($unitId, $filters, $perPage);
    }

    public function find(int $id): ?Lesson
    {
        return $this->finder->find($id);
    }

    public function getLessonForUser(Lesson $lesson, Course $course, ?\Modules\Auth\Models\User $user): Lesson
    {
        return $this->finder->getLessonForUser($lesson, $course, $user);
    }

    public function findOrFail(int $id): Lesson
    {
        return $this->finder->findOrFail($id);
    }

    public function create(int $unitId, CreateLessonDTO|array $data): Lesson
    {
        return $this->orderingProcessor->create($unitId, $data);
    }

    public function update(int $id, UpdateLessonDTO|array $data): Lesson
    {
        $lesson = $this->findOrFail($id);
        return $this->orderingProcessor->update($lesson, $data);
    }

    public function delete(int $id): bool
    {
        $lesson = $this->findOrFail($id);
        return $this->orderingProcessor->delete($lesson);
    }

    public function publish(int $id): Lesson
    {
        $lesson = $this->findOrFail($id);
        return $this->orderingProcessor->publish($lesson);
    }

    public function unpublish(int $id): Lesson
    {
        $lesson = $this->findOrFail($id);
        return $this->orderingProcessor->unpublish($lesson);
    }

    public function getRepository(): LessonRepositoryInterface
    {
        return app(LessonRepositoryInterface::class);
    }
}
