<?php

namespace Modules\Content\Services;

use Illuminate\Support\Collection;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Modules\Content\Repositories\ContentStatisticsRepository;

class ContentStatisticsService
{
    protected ContentStatisticsRepository $statisticsRepository;

    public function __construct(ContentStatisticsRepository $statisticsRepository)
    {
        $this->statisticsRepository = $statisticsRepository;
    }

    /**
     * Get statistics for a specific announcement.
     */
    public function getAnnouncementStatistics(Announcement $announcement): array
    {
        $viewCount = $announcement->views_count;
        $readCount = $this->statisticsRepository->getReadCount($announcement->id);
        $readRate = $this->statisticsRepository->calculateReadRate($announcement);
        $unreadUsers = $this->statisticsRepository->getUnreadUsers($announcement);

        return [
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'published_at' => $announcement->published_at,
            'views_count' => $viewCount,
            'read_count' => $readCount,
            'read_rate' => round($readRate, 2),
            'unread_count' => $unreadUsers->count(),
            'unread_users' => $unreadUsers,
        ];
    }

    /**
     * Get statistics for a specific news article.
     */
    public function getNewsStatistics(News $news): array
    {
        $viewCount = $news->views_count;
        $readCount = $news->reads()->count();
        $trendingScore = $news->getTrendingScore();

        return [
            'news_id' => $news->id,
            'title' => $news->title,
            'published_at' => $news->published_at,
            'views_count' => $viewCount,
            'read_count' => $readCount,
            'trending_score' => round($trendingScore, 2),
            'is_featured' => $news->is_featured,
        ];
    }

    /**
     * Get overall statistics for all announcements.
     */
    public function getAllAnnouncementStatistics(array $filters = []): Collection
    {
        return $this->statisticsRepository->getAnnouncementStatistics($filters);
    }

    /**
     * Get overall statistics for all news.
     */
    public function getAllNewsStatistics(array $filters = []): Collection
    {
        return $this->statisticsRepository->getNewsStatistics($filters);
    }

    /**
     * Calculate read rate for an announcement.
     */
    public function calculateReadRate(Announcement $announcement): float
    {
        return $this->statisticsRepository->calculateReadRate($announcement);
    }

    /**
     * Get list of users who haven't read an announcement.
     */
    public function getUnreadUsers(Announcement $announcement): Collection
    {
        return $this->statisticsRepository->getUnreadUsers($announcement);
    }

    /**
     * Get trending news.
     */
    public function getTrendingNews(int $limit = 10): Collection
    {
        $news = News::published()
            ->where('published_at', '>=', now()->subDays(7))
            ->with(['author', 'categories'])
            ->get();

        return $news->sortByDesc(function ($item) {
            return $item->getTrendingScore();
        })->take($limit)->values();
    }

    /**
     * Get most viewed news in a time period.
     */
    public function getMostViewedNews(int $days = 30, int $limit = 10): Collection
    {
        return $this->statisticsRepository->getMostViewedNews($days, $limit);
    }

    /**
     * Get dashboard statistics summary.
     */
    public function getDashboardStatistics(): array
    {
        $totalAnnouncements = Announcement::published()->count();
        $totalNews = News::published()->count();
        $totalViews = Announcement::sum('views_count') + News::sum('views_count');

        $recentAnnouncements = Announcement::published()
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        $recentNews = News::published()
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_announcements' => $totalAnnouncements,
            'total_news' => $totalNews,
            'total_views' => $totalViews,
            'recent_announcements' => $recentAnnouncements,
            'recent_news' => $recentNews,
        ];
    }
}
