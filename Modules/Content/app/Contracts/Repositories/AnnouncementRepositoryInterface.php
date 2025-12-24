<?php

namespace Modules\Content\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;

interface AnnouncementRepositoryInterface
{
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function getAnnouncementsForCourse(int $courseId, array $filters = []): LengthAwarePaginator;

    public function findWithRelations(int $announcementId): ?Announcement;

    public function create(array $data): Announcement;

    public function update(Announcement $announcement, array $data): Announcement;

    public function delete(Announcement $announcement, ?int $deletedBy = null): bool;

    public function getScheduledForPublishing(): Collection;

    public function getUnreadCount(User $user): int;

    /**
     * Find announcement by ID
     */
    public function findById(int $id): ?Announcement;
}
