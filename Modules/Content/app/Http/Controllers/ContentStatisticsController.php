<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\Traits\HandlesFiltering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Content\Contracts\Repositories\NewsRepositoryInterface;
use Modules\Content\Contracts\Services\ContentStatisticsServiceInterface;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;

/**
 * @tags Konten & Berita
 */
class ContentStatisticsController extends Controller
{
    use ApiResponse;
    use HandlesFiltering;

    public function __construct(
        protected ContentStatisticsServiceInterface $statisticsService,
        protected NewsRepositoryInterface $newsRepository
    ) {}

    /**
     * Mengambil statistik konten keseluruhan
     *
     * **Filter yang tersedia:**
     * - `filter[type]` (string): Tipe konten. Nilai: all, announcements, news
     * - `filter[course_id]` (integer): Filter berdasarkan ID kursus
     * - `filter[category_id]` (integer): Filter berdasarkan ID kategori
     * - `filter[date_from]` (string): Filter dari tanggal (format: Y-m-d)
     * - `filter[date_to]` (string): Filter sampai tanggal (format: Y-m-d)
     *
     * @summary Statistik Konten
     *
     * @queryParam filter[type] string Tipe konten. Nilai: all, announcements, news. Example: all
     * @queryParam filter[course_id] integer Filter berdasarkan ID kursus. Example: 1
     * @queryParam filter[category_id] integer Filter berdasarkan ID kategori. Example: 5
     * @queryParam filter[date_from] string Filter dari tanggal (format: Y-m-d). Example: 2025-01-01
     * @queryParam filter[date_to] string Filter sampai tanggal (format: Y-m-d). Example: 2025-12-31
     *
     * @response 200 scenario="Success" {"success":true,"message":"Berhasil","data":{"announcements":{"total":50,"published":45},"news":{"total":30,"published":28},"dashboard":{"total_views":15000}}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', [Announcement::class]);

        $type = $request->input('filter.type', 'all');
        $params = $this->extractFilterParams($request);

        $data = [];

        if ($type === 'all' || $type === 'announcements') {
            $data['announcements'] = $this->statisticsService->getAllAnnouncementStatistics($params['filter'] ?? []);
        }

        if ($type === 'all' || $type === 'news') {
            $data['news'] = $this->statisticsService->getAllNewsStatistics($params['filter'] ?? []);
        }

        if ($type === 'all') {
            $data['dashboard'] = $this->statisticsService->getDashboardStatistics();
        }

        return $this->success($data);
    }

    /**
     * Mengambil statistik pengumuman tertentu
     *
     *
     * @summary Mengambil statistik pengumuman tertentu
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function showAnnouncement(int $id): JsonResponse
    {
        $this->authorize('viewStatistics', [Announcement::class]);

        $announcement = Announcement::findOrFail($id);
        $statistics = $this->statisticsService->getAnnouncementStatistics($announcement);

        return $this->success($statistics);
    }

    /**
     * Mengambil statistik berita tertentu
     *
     *
     * @summary Mengambil statistik berita tertentu
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function showNews(string $slug): JsonResponse
    {
        $this->authorize('viewStatistics', [News::class]);

        $news = $this->newsRepository->findBySlugOrFail($slug);
        $statistics = $this->statisticsService->getNewsStatistics($news);

        return $this->success($statistics);
    }

    /**
     * Mengambil berita trending
     *
     *
     * @summary Mengambil berita trending
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $trending = $this->statisticsService->getTrendingNews($limit);

        return $this->success($trending);
    }

    /**
     * Mengambil berita paling banyak dilihat
     *
     *
     * @summary Mengambil berita paling banyak dilihat
     *
     * @response 200 scenario="Success" {"success":true,"message":"Success","data":{"id":1,"name":"Example ContentStatistics"}}
     * @response 401 scenario="Unauthorized" {"success":false,"message":"Tidak terotorisasi."}
     *
     * @authenticated
     */
    public function mostViewed(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 10);

        $mostViewed = $this->statisticsService->getMostViewedNews($days, $limit);

        return $this->success($mostViewed);
    }
}
