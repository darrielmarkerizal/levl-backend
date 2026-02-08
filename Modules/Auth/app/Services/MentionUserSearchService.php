<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class MentionUserSearchService
{
    public function search(Course $course, string $query, int $limit): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $userIds = $this->resolveCourseUserIds($course);

        if ($userIds === []) {
            return collect();
        }

        $searchIds = User::search($query)
            ->take($limit * 5)
            ->get()
            ->pluck('id')
            ->toArray();

        if ($searchIds === []) {
            return collect();
        }

        $filteredIds = array_values(array_intersect($searchIds, $userIds));

        if ($filteredIds === []) {
            return collect();
        }

        $filteredIds = array_slice($filteredIds, 0, $limit);

        $users = User::query()
            ->select(['id', 'name', 'username'])
            ->with('media')
            ->active()
            ->whereIn('id', $filteredIds)
            ->get()
            ->keyBy('id');

        return collect($filteredIds)
            ->map(fn (int $id) => $users->get($id))
            ->filter()
            ->values();
    }


    private function resolveCourseUserIds(Course $course): array
    {
        $enrolledIds = Enrollment::where('course_id', $course->id)
            ->pluck('user_id')
            ->toArray();

        $adminIds = $course->admins()->pluck('users.id')->toArray();

        $ids = array_merge($enrolledIds, $adminIds);

        if ($course->instructor_id) {
            $ids[] = $course->instructor_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
