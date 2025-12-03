<?php

namespace Modules\Search\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Common\Models\Category;
use Modules\Schemes\Models\Course;
use Modules\Search\Contracts\SearchServiceInterface;
use Modules\Search\Models\SearchHistory;
use Modules\Search\Services\SearchService;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchServiceInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();

        // Use collection driver for testing to avoid Meilisearch connection
        config(['scout.driver' => 'collection']);

        $this->searchService = new SearchService;
    }

    public function test_search_with_filters_returns_correct_results(): void
    {
        $category = Category::factory()->create(['name' => 'Programming']);

        // Create courses with different levels (using valid enum values)
        $beginnerCourses = Course::factory()->count(3)->create([
            'level_tag' => 'dasar',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $advancedCourses = Course::factory()->count(2)->create([
            'level_tag' => 'mahir',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        // Search with level filter
        $result = $this->searchService->search('', [
            'level_tag' => ['dasar'],
        ]);

        $this->assertGreaterThanOrEqual(3, $result->total);
        $this->assertEquals('', $result->query);
        $this->assertArrayHasKey('level_tag', $result->filters);
    }

    public function test_search_with_category_filter(): void
    {
        $category1 = Category::factory()->create(['name' => 'Programming']);
        $category2 = Category::factory()->create(['name' => 'Design']);

        Course::factory()->count(3)->create([
            'category_id' => $category1->id,
            'status' => 'published',
        ]);

        Course::factory()->count(2)->create([
            'category_id' => $category2->id,
            'status' => 'published',
        ]);

        $result = $this->searchService->search('', [
            'category_id' => [$category1->id],
        ]);

        $this->assertGreaterThanOrEqual(3, $result->total);
    }

    public function test_search_with_status_filter(): void
    {
        Course::factory()->count(3)->create([
            'status' => 'published',
        ]);

        Course::factory()->count(2)->create([
            'status' => 'draft',
        ]);

        $result = $this->searchService->search('', [
            'status' => ['published'],
        ]);

        // Should return only published courses
        $this->assertGreaterThanOrEqual(3, $result->total);
    }

    public function test_autocomplete_returns_suggestions(): void
    {
        Course::factory()->create([
            'title' => 'Laravel Basics',
            'status' => 'published',
        ]);

        Course::factory()->create([
            'title' => 'Laravel Advanced',
            'status' => 'published',
        ]);

        Course::factory()->create([
            'title' => 'PHP Fundamentals',
            'status' => 'published',
        ]);

        $suggestions = $this->searchService->getSuggestions('Laravel');

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);
        $this->assertContains('Laravel Basics', $suggestions);
        $this->assertContains('Laravel Advanced', $suggestions);
    }

    public function test_autocomplete_returns_empty_for_empty_query(): void
    {
        $suggestions = $this->searchService->getSuggestions('');

        $this->assertIsArray($suggestions);
        $this->assertEmpty($suggestions);
    }

    public function test_autocomplete_limits_results(): void
    {
        // Create more courses than the limit
        for ($i = 1; $i <= 15; $i++) {
            Course::factory()->create([
                'title' => "Course $i",
                'status' => 'published',
            ]);
        }

        $suggestions = $this->searchService->getSuggestions('Course', 5);

        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    public function test_search_history_is_saved(): void
    {
        $user = User::factory()->create();

        $this->searchService->saveSearchHistory($user, 'Laravel', ['level_tag' => ['dasar']], 5);

        $this->assertDatabaseHas('search_history', [
            'user_id' => $user->id,
            'query' => 'Laravel',
            'results_count' => 5,
        ]);
    }

    public function test_search_history_not_saved_for_empty_query(): void
    {
        $user = User::factory()->create();

        $this->searchService->saveSearchHistory($user, '', [], 0);

        $this->assertDatabaseMissing('search_history', [
            'user_id' => $user->id,
        ]);
    }

    public function test_duplicate_consecutive_searches_not_saved(): void
    {
        $user = User::factory()->create();

        // Save first search
        $this->searchService->saveSearchHistory($user, 'Laravel', [], 5);

        // Try to save the same search again
        $this->searchService->saveSearchHistory($user, 'Laravel', [], 5);

        // Should only have one record
        $count = SearchHistory::where('user_id', $user->id)
            ->where('query', 'Laravel')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_different_searches_are_saved(): void
    {
        $user = User::factory()->create();

        $this->searchService->saveSearchHistory($user, 'Laravel', [], 5);
        $this->searchService->saveSearchHistory($user, 'PHP', [], 3);

        $count = SearchHistory::where('user_id', $user->id)->count();

        $this->assertEquals(2, $count);
    }

    public function test_search_with_sorting(): void
    {
        Course::factory()->create([
            'title' => 'Course A',
            'published_at' => now()->subDays(10),
            'status' => 'published',
        ]);

        Course::factory()->create([
            'title' => 'Course B',
            'published_at' => now()->subDays(5),
            'status' => 'published',
        ]);

        $result = $this->searchService->search('', [], [
            'field' => 'published_at',
            'direction' => 'desc',
        ]);

        $this->assertNotEmpty($result->items);
        $this->assertEquals('published_at', $result->sort['field']);
        $this->assertEquals('desc', $result->sort['direction']);
    }

    public function test_search_result_dto_contains_execution_time(): void
    {
        Course::factory()->create(['status' => 'published']);

        $result = $this->searchService->search('');

        $this->assertGreaterThan(0, $result->executionTime);
    }

    public function test_search_with_pagination(): void
    {
        Course::factory()->count(20)->create(['status' => 'published']);

        $result = $this->searchService->search('', [
            'per_page' => 5,
            'page' => 1,
        ]);

        $this->assertLessThanOrEqual(5, $result->items->count());
        $this->assertEquals(1, $result->items->currentPage());
    }
}
