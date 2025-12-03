<?php

namespace App\Contracts\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;

interface ContentServiceInterface
{
    /**
     * Create a new announcement.
     *
     * @param  array  $data  Announcement data including title, content, target_type, etc.
     * @param  User  $author  The user creating the announcement
     * @return Announcement The created announcement
     *
     * @throws \Exception If required fields are missing or creation fails
     */
    public function createAnnouncement(array $data, User $author): Announcement;

    /**
     * Create a new news article.
     *
     * @param  array  $data  News data including title, content, excerpt, etc.
     * @param  User  $author  The user creating the news article
     * @return News The created news article
     *
     * @throws \Exception If required fields are missing or creation fails
     */
    public function createNews(array $data, User $author): News;

    /**
     * Update an announcement.
     *
     * @param  Announcement  $announcement  The announcement to update
     * @param  array  $data  Update data
     * @param  User  $editor  The user performing the update
     * @return Announcement The updated announcement
     */
    public function updateAnnouncement(Announcement $announcement, array $data, User $editor): Announcement;

    /**
     * Update a news article.
     *
     * @param  News  $news  The news article to update
     * @param  array  $data  Update data
     * @param  User  $editor  The user performing the update
     * @return News The updated news article
     */
    public function updateNews(News $news, array $data, User $editor): News;

    /**
     * Publish content immediately.
     *
     * @param  Announcement|News  $content  The content to publish
     * @return bool True if publication was successful
     */
    public function publishContent($content): bool;

    /**
     * Schedule content for future publication.
     *
     * @param  Announcement|News  $content  The content to schedule
     * @param  \Carbon\Carbon  $publishAt  The scheduled publication time
     * @return bool True if scheduling was successful
     *
     * @throws \Exception If scheduled time is in the past
     */
    public function scheduleContent($content, \Carbon\Carbon $publishAt): bool;

    /**
     * Cancel scheduled publication.
     *
     * @param  Announcement|News  $content  The content to unschedule
     * @return bool True if cancellation was successful
     */
    public function cancelSchedule($content): bool;

    /**
     * Delete content (soft delete).
     *
     * @param  Announcement|News  $content  The content to delete
     * @param  User  $user  The user performing the deletion
     * @return bool True if deletion was successful
     */
    public function deleteContent($content, User $user): bool;

    /**
     * Get announcements for a user.
     *
     * @param  User  $user  The user to get announcements for
     * @param  array  $filters  Optional filters
     * @return LengthAwarePaginator Paginated announcements
     */
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * Get news feed.
     *
     * @param  array  $filters  Optional filters
     * @return LengthAwarePaginator Paginated news articles
     */
    public function getNewsFeed(array $filters = []): LengthAwarePaginator;

    /**
     * Search content.
     *
     * @param  string  $query  The search query
     * @param  string  $type  Content type: 'news', 'announcement', or 'all'
     * @param  array  $filters  Optional filters
     * @return LengthAwarePaginator Paginated search results
     */
    public function searchContent(string $query, string $type = 'all', array $filters = []): LengthAwarePaginator;

    /**
     * Mark content as read by user.
     *
     * @param  Announcement|News  $content  The content to mark as read
     * @param  User  $user  The user marking the content as read
     */
    public function markAsRead($content, User $user): void;

    /**
     * Increment view count for content.
     *
     * @param  Announcement|News  $content  The content to increment views for
     */
    public function incrementViews($content): void;

    /**
     * Get trending news.
     *
     * @param  int  $limit  Maximum number of trending news to return
     * @return Collection Collection of trending news articles
     */
    public function getTrendingNews(int $limit = 10): Collection;

    /**
     * Get featured news.
     *
     * @param  int  $limit  Maximum number of featured news to return
     * @return Collection Collection of featured news articles
     */
    public function getFeaturedNews(int $limit = 5): Collection;
}
