<?php

namespace Modules\Content\Services;

use App\Contracts\Services\ContentServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\ContentRevision;
use Modules\Content\Models\News;
use Modules\Content\Repositories\AnnouncementRepository;
use Modules\Content\Repositories\NewsRepository;

class ContentService implements ContentServiceInterface
{
    protected AnnouncementRepository $announcementRepository;

    protected NewsRepository $newsRepository;

    public function __construct(
        AnnouncementRepository $announcementRepository,
        NewsRepository $newsRepository
    ) {
        $this->announcementRepository = $announcementRepository;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Create a new announcement.
     *
     * @throws \Exception
     */
    public function createAnnouncement(array $data, User $author): Announcement
    {
        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            throw new \Exception('Title and content are required.');
        }

        return DB::transaction(function () use ($data, $author) {
            $announcementData = [
                'author_id' => $author->id,
                'course_id' => $data['course_id'] ?? null,
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => $data['status'] ?? 'draft',
                'target_type' => $data['target_type'] ?? 'all',
                'target_value' => $data['target_value'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
            ];

            return $this->announcementRepository->create($announcementData);
        });
    }

    /**
     * Create a new news article.
     *
     * @throws \Exception
     */
    public function createNews(array $data, User $author): News
    {
        // Validate required fields
        if (empty($data['title']) || empty($data['content'])) {
            throw new \Exception('Title and content are required.');
        }

        return DB::transaction(function () use ($data, $author) {
            $newsData = [
                'author_id' => $author->id,
                'title' => $data['title'],
                'slug' => $data['slug'] ?? null,
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'featured_image_path' => $data['featured_image_path'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'is_featured' => $data['is_featured'] ?? false,
                'category_ids' => $data['category_ids'] ?? [],
                'tag_ids' => $data['tag_ids'] ?? [],
            ];

            return $this->newsRepository->create($newsData);
        });
    }

    /**
     * Update announcement.
     */
    public function updateAnnouncement(Announcement $announcement, array $data, User $editor): Announcement
    {
        return DB::transaction(function () use ($announcement, $data, $editor) {
            // Save revision before updating
            $this->saveRevision($announcement, $editor);

            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }

            if (isset($data['content'])) {
                $updateData['content'] = $data['content'];
            }

            if (isset($data['target_type'])) {
                $updateData['target_type'] = $data['target_type'];
            }

            if (isset($data['target_value'])) {
                $updateData['target_value'] = $data['target_value'];
            }

            if (isset($data['priority'])) {
                $updateData['priority'] = $data['priority'];
            }

            return $this->announcementRepository->update($announcement, $updateData);
        });
    }

    /**
     * Update news.
     */
    public function updateNews(News $news, array $data, User $editor): News
    {
        return DB::transaction(function () use ($news, $data, $editor) {
            // Save revision before updating
            $this->saveRevision($news, $editor);

            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }

            if (isset($data['content'])) {
                $updateData['content'] = $data['content'];
            }

            if (isset($data['excerpt'])) {
                $updateData['excerpt'] = $data['excerpt'];
            }

            if (isset($data['featured_image_path'])) {
                $updateData['featured_image_path'] = $data['featured_image_path'];
            }

            if (isset($data['is_featured'])) {
                $updateData['is_featured'] = $data['is_featured'];
            }

            if (isset($data['category_ids'])) {
                $updateData['category_ids'] = $data['category_ids'];
            }

            if (isset($data['tag_ids'])) {
                $updateData['tag_ids'] = $data['tag_ids'];
            }

            return $this->newsRepository->update($news, $updateData);
        });
    }

    /**
     * Publish content immediately.
     */
    public function publishContent($content): bool
    {
        return DB::transaction(function () use ($content) {
            $content->update([
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

            // Fire published event
            if ($content instanceof Announcement) {
                event(new \Modules\Content\Events\AnnouncementPublished($content));
            } elseif ($content instanceof News) {
                event(new \Modules\Content\Events\NewsPublished($content));
            }

            return true;
        });
    }

    /**
     * Schedule content for future publication.
     */
    public function scheduleContent($content, \Carbon\Carbon $publishAt): bool
    {
        if ($publishAt->isPast()) {
            throw new \Exception('Scheduled time must be in the future.');
        }

        return $content->update([
            'status' => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);
    }

    /**
     * Cancel scheduled publication.
     */
    public function cancelSchedule($content): bool
    {
        return $content->update([
            'status' => 'draft',
            'scheduled_at' => null,
        ]);
    }

    /**
     * Delete content (soft delete).
     */
    public function deleteContent($content, User $user): bool
    {
        if ($content instanceof Announcement) {
            return $this->announcementRepository->delete($content, $user->id);
        } elseif ($content instanceof News) {
            return $this->newsRepository->delete($content, $user->id);
        }

        return false;
    }

    /**
     * Get announcements for a user.
     */
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->announcementRepository->getAnnouncementsForUser($user, $filters);
    }

    /**
     * Get news feed.
     */
    public function getNewsFeed(array $filters = []): LengthAwarePaginator
    {
        return $this->newsRepository->getNewsFeed($filters);
    }

    /**
     * Search content.
     */
    public function searchContent(string $query, string $type = 'all', array $filters = []): LengthAwarePaginator
    {
        if ($type === 'news' || $type === 'all') {
            return $this->newsRepository->searchNews($query, $filters);
        }

        // For announcements, we would need to implement search in repository
        return $this->newsRepository->searchNews($query, $filters);
    }

    /**
     * Mark content as read by user.
     */
    public function markAsRead($content, User $user): void
    {
        if ($content instanceof Announcement) {
            $content->markAsReadBy($user);
        } elseif ($content instanceof News) {
            $content->reads()->firstOrCreate([
                'user_id' => $user->id,
                'readable_type' => News::class,
                'readable_id' => $content->id,
            ]);
        }
    }

    /**
     * Increment view count.
     */
    public function incrementViews($content): void
    {
        if ($content instanceof News) {
            $content->incrementViews();
        } elseif ($content instanceof Announcement) {
            $content->increment('views_count');
        }
    }

    /**
     * Save content revision.
     */
    protected function saveRevision($content, User $editor, ?string $note = null): void
    {
        ContentRevision::create([
            'content_type' => get_class($content),
            'content_id' => $content->id,
            'editor_id' => $editor->id,
            'title' => $content->title,
            'content' => $content->content,
            'revision_note' => $note,
        ]);
    }

    /**
     * Get trending news.
     */
    public function getTrendingNews(int $limit = 10): Collection
    {
        return $this->newsRepository->getTrendingNews($limit);
    }

    /**
     * Get featured news.
     */
    public function getFeaturedNews(int $limit = 5): Collection
    {
        return $this->newsRepository->getFeaturedNews($limit);
    }
}
