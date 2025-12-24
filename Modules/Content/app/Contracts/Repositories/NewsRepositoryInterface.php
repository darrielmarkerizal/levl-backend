<?php

namespace Modules\Content\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Content\Models\News;

interface NewsRepositoryInterface
{
    public function getNewsFeed(array $filters = []): LengthAwarePaginator;

    public function searchNews(string $searchQuery, array $filters = []): LengthAwarePaginator;

    public function findBySlugWithRelations(string $slug): ?News;

    public function findWithRelations(int $newsId): ?News;

    public function create(array $data): News;

    public function update(News $news, array $data): News;

    public function delete(News $news, ?int $deletedBy = null): bool;

    public function getTrendingNews(int $limit = 10): Collection;

    public function getFeaturedNews(int $limit = 5): Collection;

    public function getScheduledForPublishing(): Collection;

    /**
     * Find news by slug or fail
     */
    public function findBySlugOrFail(string $slug): News;
}
