<?php

namespace Modules\Content\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Content\Contracts\Repositories\AnnouncementRepositoryInterface;
use Modules\Content\Models\Announcement;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::published()
            ->forUser($user)
            ->with(['author', 'course'])
            ->withCount('reads');

        if (isset($filters['course_id'])) {
            $query->forCourse($filters['course_id']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['unread']) && $filters['unread']) {
            $query->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $query->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('published_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getAnnouncementsForCourse(int $courseId, array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::published()
            ->forCourse($courseId)
            ->with(['author'])
            ->withCount('reads');

        $query->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('published_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findWithRelations(int $announcementId): ?Announcement
    {
        return Announcement::with(['author', 'course', 'reads', 'revisions.editor'])
            ->withCount('reads')
            ->find($announcementId);
    }

    public function create(array $data): Announcement
    {
        return Announcement::create($data);
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update($data);

        return $announcement->fresh();
    }

    public function delete(Announcement $announcement, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $announcement->deleted_by = $deletedBy;
            $announcement->save();
        }

        return $announcement->delete();
    }

    public function getScheduledForPublishing(): Collection
    {
        return Announcement::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    public function getUnreadCount(User $user): int
    {
        return Announcement::published()
            ->forUser($user)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }

    public function findById(int $id): ?Announcement
    {
        return Announcement::find($id);
    }
}
