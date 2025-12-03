<?php

namespace Modules\Content\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;

class AnnouncementRepository
{
    /**
     * Get announcements for a specific user with targeting logic.
     */
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::published()
            ->forUser($user)
            ->with(['author', 'course'])
            ->withCount('reads');

        // Apply filters
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

        // Sort by priority and published date
        $query->orderByRaw("FIELD(priority, 'high', 'normal', 'low')")
            ->orderBy('published_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get announcements for a specific course.
     */
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

    /**
     * Find an announcement by ID with relationships.
     */
    public function findWithRelations(int $announcementId): ?Announcement
    {
        return Announcement::with(['author', 'course', 'reads', 'revisions.editor'])
            ->withCount('reads')
            ->find($announcementId);
    }

    /**
     * Create a new announcement.
     */
    public function create(array $data): Announcement
    {
        return Announcement::create($data);
    }

    /**
     * Update an announcement.
     */
    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update($data);

        return $announcement->fresh();
    }

    /**
     * Delete an announcement (soft delete).
     */
    public function delete(Announcement $announcement, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $announcement->deleted_by = $deletedBy;
            $announcement->save();
        }

        return $announcement->delete();
    }

    /**
     * Get scheduled announcements that are ready to publish.
     */
    public function getScheduledForPublishing(): Collection
    {
        return Announcement::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    /**
     * Get unread announcements count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Announcement::published()
            ->forUser($user)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }
}
