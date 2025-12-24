<?php

namespace Modules\Content\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Content\Contracts\Repositories\NewsRepositoryInterface;
use Modules\Content\Models\News;

class NewsRepository implements NewsRepositoryInterface
{
    public function getNewsFeed(array $filters = []): LengthAwarePaginator
    {
        $query = News::published()
            ->with(['author', 'categories', 'tags'])
            ->withCount('reads');

        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('content_categories.id', $filters['category_id']);
            });
        }

        if (isset($filters['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag_id']);
            });
        }

        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        if (isset($filters['date_from'])) {
            $query->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('published_at', '<=', $filters['date_to']);
        }

        $query->orderBy('is_featured', 'desc')
            ->orderBy('published_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function searchNews(string $searchQuery, array $filters = []): LengthAwarePaginator
    {
        $query = News::published()
            ->whereRaw('MATCH(title, excerpt, content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery])
            ->with(['author', 'categories', 'tags'])
            ->withCount('reads');

        if (isset($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('content_categories.id', $filters['category_id']);
            });
        }

        $query->orderBy('is_featured', 'desc')
            ->orderBy('published_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function findBySlugWithRelations(string $slug): ?News
    {
        return News::where('slug', $slug)
            ->with(['author', 'categories', 'tags', 'revisions.editor'])
            ->withCount('reads')
            ->first();
    }

    public function findWithRelations(int $newsId): ?News
    {
        return News::with(['author', 'categories', 'tags', 'revisions.editor'])
            ->withCount('reads')
            ->find($newsId);
    }

    public function create(array $data): News
    {
        $news = News::create($data);

        if (isset($data['category_ids'])) {
            $news->categories()->sync($data['category_ids']);
        }

        if (isset($data['tag_ids'])) {
            $news->tags()->sync($data['tag_ids']);
        }

        return $news->fresh(['categories', 'tags']);
    }

    public function update(News $news, array $data): News
    {
        $news->update($data);

        if (isset($data['category_ids'])) {
            $news->categories()->sync($data['category_ids']);
        }

        if (isset($data['tag_ids'])) {
            $news->tags()->sync($data['tag_ids']);
        }

        return $news->fresh(['categories', 'tags']);
    }

    public function delete(News $news, ?int $deletedBy = null): bool
    {
        if ($deletedBy) {
            $news->deleted_by = $deletedBy;
            $news->save();
        }

        return $news->delete();
    }

    public function getTrendingNews(int $limit = 10): Collection
    {
        return News::published()
            ->where('published_at', '>=', now()->subDays(7))
            ->with(['author', 'categories'])
            ->orderByRaw('views_count / (TIMESTAMPDIFF(HOUR, published_at, NOW()) + 1) DESC')
            ->limit($limit)
            ->get();
    }

    public function getFeaturedNews(int $limit = 5): Collection
    {
        return News::published()
            ->featured()
            ->with(['author', 'categories'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getScheduledForPublishing(): Collection
    {
        return News::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    public function findBySlugOrFail(string $slug): News
    {
        return News::where('slug', $slug)->firstOrFail();
    }
}
