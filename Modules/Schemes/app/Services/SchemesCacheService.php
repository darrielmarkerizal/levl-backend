<?php

namespace Modules\Schemes\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Unit;

class SchemesCacheService
{
    private const TTL_COURSE = 3600;      
    private const TTL_LISTING = 300;      
    private const TTL_UNITS = 3600;       
    
    
    public function getCourse(int $id): ?Course
    {
        return Cache::tags(['schemes', 'courses'])
            ->remember("course.{$id}", self::TTL_COURSE, function () use ($id) {
                return Course::with(['instructor', 'tags', 'category', 'media'])
                    ->find($id);
            });
    }
    
    
    public function getCourseBySlug(string $slug): ?Course
    {
        return Cache::tags(['schemes', 'courses'])
            ->remember("course.slug.{$slug}", self::TTL_COURSE, function () use ($slug) {
                return Course::where('slug', $slug)
                    ->with(['instructor', 'tags', 'category', 'media', 'units.lessons'])
                    ->first();
            });
    }
    
    
    public function getPublicCourses(int $page, int $perPage, array $filters, callable $callback): LengthAwarePaginator
    {
        
        $filterKey = md5(json_encode($filters));
        
        return Cache::tags(['schemes', 'courses', 'listing'])
            ->remember("courses.public.{$page}.{$perPage}.{$filterKey}", self::TTL_LISTING, $callback);
    }
    
    
    public function invalidateCourse(int $courseId, ?string $slug = null): void
    {
        Cache::tags(['schemes', 'courses'])->forget("course.{$courseId}");
        
        if ($slug) {
            Cache::tags(['schemes', 'courses'])->forget("course.slug.{$slug}");
        }
        
        
        Cache::tags(['schemes', 'listing'])->flush();
    }
    
    
    public function invalidateListings(): void
    {
        Cache::tags(['schemes', 'listing'])->flush();
    }
}
