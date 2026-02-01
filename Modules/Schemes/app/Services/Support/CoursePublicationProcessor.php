<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use App\Exceptions\BusinessException;
use Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Services\SchemesCacheService;

class CoursePublicationProcessor
{
    public function __construct(
        private readonly CourseRepositoryInterface $repository,
        private readonly SchemesCacheService $cacheService
    ) {}

    public function publish(Course $course): Course
    {
        if ($course->units()->count() === 0) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_units'),
                ['units' => [__('messages.courses.must_have_one_unit')]]
            );
        }

        $publishedUnitsCount = $course->units()->where('status', 'published')->count();
        if ($publishedUnitsCount === 0) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_published_units'),
                ['units' => [__('messages.courses.must_have_one_published_unit')]]
            );
        }

        $hasLessons = $course->units()->whereHas('lessons')->exists();
        if (! $hasLessons) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_lessons'),
                ['lessons' => [__('messages.courses.must_have_one_lesson')]]
            );
        }

        $hasPublishedLessons = $course->units()
            ->where('status', 'published')
            ->whereHas('lessons', fn ($q) => $q->where('status', 'published'))
            ->exists();

        if (! $hasPublishedLessons) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_published_lessons'),
                ['lessons' => [__('messages.courses.must_have_one_published_lesson')]]
            );
        }

        if ($course->enrollment_type === 'key_based' && empty($course->enrollment_key_hash)) {
            throw new BusinessException(
                __('messages.courses.cannot_publish_without_enrollment_key'),
                ['enrollment_key' => [__('messages.courses.must_have_enrollment_key')]]
            );
        }

        $this->repository->update($course, [
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->cacheService->invalidateCourse($course->id, $course->slug);

        $actor = auth()->user();
        if ($actor) {
            dispatch(new \App\Jobs\LogActivityJob([
                'log_name' => 'schemes',
                'causer_id' => $actor->id,
                'description' => "Published course: {$course->title}",
                'properties' => ['course_id' => $course->id, 'action' => 'publish'],
            ]));
        }

        return $course->fresh();
    }

    public function unpublish(Course $course): Course
    {
        $this->repository->update($course, [
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->cacheService->invalidateCourse($course->id, $course->slug);

        $actor = auth()->user();
        if ($actor) {
            dispatch(new \App\Jobs\LogActivityJob([
                'log_name' => 'schemes',
                'causer_id' => $actor->id,
                'description' => "Unpublished course: {$course->title}",
                'properties' => ['course_id' => $course->id, 'action' => 'unpublish'],
            ]));
        }

        return $course->fresh();
    }
}
