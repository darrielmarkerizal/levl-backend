<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Auth\Contracts\Services\UserActivityServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;

class UserActivityService implements UserActivityServiceInterface
{
    public function logActivity(User $user, string $type, array $data, ?Model $related = null)
    {
        $activity = activity('user_activity')
            ->causedBy($user)
            ->withProperties($data)
            ->event($type);

        if ($related) {
            $activity->performedOn($related);
        }

        return $activity->log($type);
    }

    public function getActivities(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ActivityLog::queries() // using queries() if available or query()
            ->where('log_name', 'user_activity')
            ->where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->orderBy('created_at', 'desc');

        if (! empty($filters['type'])) {
            $query->where('event', $filters['type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    public function getRecentActivities(User $user, int $limit = 10): Collection
    {
        return ActivityLog::query()
            ->where('log_name', 'user_activity')
            ->where('causer_type', User::class)
            ->where('causer_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function logEnrollment(User $user, $course): void
    {
        $this->logActivity($user, UserActivity::TYPE_ENROLLMENT, [
            'course_id' => $course->id,
            'course_name' => $course->name,
        ], $course);
    }

    public function logCompletion(User $user, $course): void
    {
        $this->logActivity($user, UserActivity::TYPE_COMPLETION, [
            'course_id' => $course->id,
            'course_name' => $course->name,
        ], $course);
    }

    public function logSubmission(User $user, $assignment): void
    {
        $this->logActivity($user, UserActivity::TYPE_SUBMISSION, [
            'assignment_id' => $assignment->id,
            'assignment_name' => $assignment->name ?? 'Assignment',
        ], $assignment);
    }
}
