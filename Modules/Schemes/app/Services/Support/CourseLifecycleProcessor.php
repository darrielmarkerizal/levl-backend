<?php

declare(strict_types=1);

namespace Modules\Schemes\Services\Support;

use App\Exceptions\DuplicateResourceException;
use App\Support\CodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface;
use Modules\Schemes\DTOs\CreateCourseDTO;
use Modules\Schemes\DTOs\UpdateCourseDTO;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Services\SchemesCacheService;

class CourseLifecycleProcessor
{
    public function __construct(
        private readonly CourseRepositoryInterface $repository,
        private readonly SchemesCacheService $cacheService
    ) {}

    public function create(CreateCourseDTO|array $data, ?User $actor = null, array $files = []): Course
    {
        try {
            return DB::transaction(function () use ($data, $actor, $files) {
                $attributes = $data instanceof CreateCourseDTO ? $data->toArrayWithoutNull() : $data;

                if (! isset($attributes['code'])) {
                    $attributes['code'] = $this->generateCourseCode();
                }

                if (! isset($attributes['instructor_id'])) {
                    $attributes['instructor_id'] = null;
                }

                $tags = $attributes['tags'] ?? null;
                $attributes = Arr::except($attributes, ['slug', 'tags']);

                $course = $this->repository->create($attributes);

                if ($tags) {
                    $course->tags()->sync($tags);
                }

                if ($actor && $actor->hasRole(['Superadmin', 'Admin'])) {
                    $course->admins()->syncWithoutDetaching([$actor->id]);
                }

                $this->handleMedia($course, $files);
                $this->cacheService->invalidateListings();

                $course = $course->fresh(['tags']);

                if ($actor) {
                    dispatch(new \App\Jobs\LogActivityJob([
                        'log_name' => 'schemes',
                        'causer_id' => $actor->id,
                        'description' => "Created course: {$course->title} ({$course->code})",
                        'properties' => ['course_id' => $course->id, 'action' => 'create'],
                    ]));
                }

                return $course;
            });
        } catch (QueryException $e) {
            throw new DuplicateResourceException($this->parseCourseDuplicates($e));
        }
    }

    public function update(Course $course, UpdateCourseDTO|array $data, array $files = []): Course
    {
        try {
            return DB::transaction(function () use ($course, $data, $files) {
                $attributes = $data instanceof UpdateCourseDTO ? $data->toArrayWithoutNull() : $data;

                $tags = $attributes['tags'] ?? null;
                $attributes = Arr::except($attributes, ['slug', 'tags']);

                $this->repository->update($course, $attributes);

                if ($tags !== null) {
                    $course->tags()->sync($tags);
                }

                $this->handleMedia($course, $files);
                $this->cacheService->invalidateCourse($course->id, $course->slug);

                $updatedCourse = $course->fresh(['tags']);

                $actor = auth()->user();
                if ($actor) {
                    dispatch(new \App\Jobs\LogActivityJob([
                        'log_name' => 'schemes',
                        'causer_id' => $actor->id,
                        'description' => "Updated course: {$updatedCourse->title}",
                        'properties' => ['course_id' => $course->id, 'action' => 'update', 'changes' => $attributes],
                    ]));
                }

                return $updatedCourse;
            });
        } catch (QueryException $e) {
            throw new DuplicateResourceException($this->parseCourseDuplicates($e));
        }
    }

    public function delete(Course $course): bool
    {
        $deleted = $this->repository->delete($course);

        if ($deleted) {
            $actor = auth()->user();
            if ($actor) {
                dispatch(new \App\Jobs\LogActivityJob([
                    'log_name' => 'schemes',
                    'causer_id' => $actor->id,
                    'description' => "Deleted course: {$course->title}",
                    'properties' => ['course_id' => $course->id, 'action' => 'delete'],
                ]));
            }
        }

        return $deleted;
    }

    public function updateEnrollmentSettings(Course $course, array $data): array
    {
        $plainKey = null;

        if ($data['enrollment_type'] === 'key_based' && empty($data['enrollment_key'])) {
            $plainKey = $this->generateEnrollmentKey(12);
            $data['enrollment_key'] = $plainKey;
        } elseif ($data['enrollment_type'] === 'key_based' && ! empty($data['enrollment_key'])) {
            $plainKey = $data['enrollment_key'];
        }

        if ($data['enrollment_type'] !== 'key_based') {
            $data['enrollment_key'] = null;
        }

        $updated = $this->update($course, $data);

        return [
            'course' => $updated,
            'enrollment_key' => $plainKey,
        ];
    }

    public function uploadThumbnail(Course $course, UploadedFile $file): Course
    {
        $course->clearMediaCollection('thumbnail');
        $course->addMedia($file)->toMediaCollection('thumbnail');

        return $course->fresh();
    }

    public function uploadBanner(Course $course, UploadedFile $file): Course
    {
        $course->clearMediaCollection('banner');
        $course->addMedia($file)->toMediaCollection('banner');

        return $course->fresh();
    }

    public function deleteThumbnail(Course $course): Course
    {
        $course->clearMediaCollection('thumbnail');

        return $course->fresh();
    }

    public function deleteBanner(Course $course): Course
    {
        $course->clearMediaCollection('banner');

        return $course->fresh();
    }
    
    public function generateEnrollmentKey(int $length = 12): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    private function handleMedia(Course $course, array $files): void
    {
        foreach (['thumbnail', 'banner'] as $collection) {
            if (! empty($files[$collection])) {
                $course->clearMediaCollection($collection);
                $course->addMedia($files[$collection])->toMediaCollection($collection);
            }
        }
    }

    private function parseCourseDuplicates(QueryException $e): array
    {
        $message = $e->getMessage();
        $errors = [];

        if (preg_match('/courses?_code_unique/i', $message)) {
            $errors['code'] = [__('messages.courses.code_exists')];
        }

        if (preg_match('/courses?_slug_unique/i', $message)) {
            $errors['slug'] = [__('messages.courses.slug_exists')];
        }

        if (empty($errors)) {
            if (preg_match('/Key \(([^)]+)\)=\([^)]+\) already exists/i', $message, $matches)) {
                $column = $matches[1];
                $errors[$column] = [__('messages.courses.duplicate_data_field', ['field' => $column])];
            }
        }

        return $errors ?: ['general' => [__('messages.courses.duplicate_data')]];
    }

    private function generateCourseCode(): string
    {
        return CodeGenerator::generate('CRS-', 6, Course::class);
    }
}
