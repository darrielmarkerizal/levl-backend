<?php

namespace Modules\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Search\Models\SearchHistory;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authenticated tests
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    /** @test */
    public function can_search_courses_without_authentication()
    {
        $response = $this->getJson('/api/v1/search/courses?query=Laravel');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'query',
                    'filters',
                    'sort',
                    'total',
                    'execution_time',
                    'suggestions',
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /** @test */
    public function search_returns_filtered_results_by_level()
    {
        $response = $this->getJson('/api/v1/search/courses?query=&level_tag[]=beginner');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Check that filters were applied
        $meta = $response->json('meta');
        $this->assertArrayHasKey('level_tag', $meta['filters']);
    }

    /** @test */
    public function search_returns_filtered_results_by_status()
    {
        $response = $this->getJson('/api/v1/search/courses?query=&status[]=published');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $meta = $response->json('meta');
        $this->assertArrayHasKey('status', $meta['filters']);
    }

    /** @test */
    public function search_supports_pagination()
    {
        $response = $this->getJson('/api/v1/search/courses?query=&per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $pagination = $response->json('pagination');
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(1, $pagination['current_page']);
    }

    /** @test */
    public function search_saves_history_for_authenticated_users()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/search/courses?query=Laravel');

        $response->assertStatus(200);

        // Check that search history was saved
        $this->assertDatabaseHas('search_history', [
            'user_id' => $this->user->id,
            'query' => 'Laravel',
        ]);
    }

    /** @test */
    public function search_does_not_save_empty_queries_to_history()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/search/courses?query=');

        $response->assertStatus(200);

        // Check that no search history was saved
        $this->assertDatabaseMissing('search_history', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function search_returns_suggestions_when_no_results_found()
    {
        // Search for something that won't match
        $response = $this->getJson('/api/v1/search/courses?query=NonexistentCourse');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $meta = $response->json('meta');
        $this->assertIsArray($meta['suggestions']);
    }

    /** @test */
    public function can_get_autocomplete_suggestions()
    {
        $response = $this->getJson('/api/v1/search/autocomplete?query=Laravel');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        $suggestions = $response->json('data');
        $this->assertIsArray($suggestions);
    }

    /** @test */
    public function autocomplete_returns_empty_array_for_empty_query()
    {
        $response = $this->getJson('/api/v1/search/autocomplete?query=');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }

    /** @test */
    public function autocomplete_respects_limit_parameter()
    {
        $response = $this->getJson('/api/v1/search/autocomplete?query=Laravel&limit=5');

        $response->assertStatus(200);

        $suggestions = $response->json('data');
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    /** @test */
    public function authenticated_user_can_get_search_history()
    {
        // Create some search history
        SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'Laravel',
            'filters' => [],
            'results_count' => 5,
        ]);
        SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'PHP',
            'filters' => [],
            'results_count' => 3,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/search/history');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'query',
                        'filters',
                        'results_count',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function unauthenticated_user_cannot_get_search_history()
    {
        $response = $this->getJson('/api/v1/search/history');

        $response->assertStatus(401);
    }

    /** @test */
    public function search_history_respects_limit_parameter()
    {
        // Create many search history entries
        for ($i = 0; $i < 25; $i++) {
            SearchHistory::create([
                'user_id' => $this->user->id,
                'query' => "Query $i",
                'filters' => [],
                'results_count' => $i,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/search/history?limit=10');

        $response->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function authenticated_user_can_clear_all_search_history()
    {
        // Create some search history
        SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'Laravel',
            'filters' => [],
            'results_count' => 5,
        ]);
        SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'PHP',
            'filters' => [],
            'results_count' => 3,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson('/api/v1/search/history');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Search history cleared successfully',
            ]);

        // Verify all history is deleted
        $this->assertDatabaseMissing('search_history', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_specific_search_history_entry()
    {
        // Create search history entries
        $history1 = SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'Laravel',
            'filters' => [],
            'results_count' => 5,
        ]);
        $history2 = SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'PHP',
            'filters' => [],
            'results_count' => 3,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/v1/search/history?id={$history1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Search history entry deleted successfully',
            ]);

        // Verify only the specific entry is deleted
        $this->assertDatabaseMissing('search_history', [
            'id' => $history1->id,
        ]);
        $this->assertDatabaseHas('search_history', [
            'id' => $history2->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_clear_search_history()
    {
        $response = $this->deleteJson('/api/v1/search/history');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_only_access_their_own_search_history()
    {
        // Create another user with search history
        $otherUser = User::factory()->create();
        SearchHistory::create([
            'user_id' => $otherUser->id,
            'query' => 'Other User Query',
            'filters' => [],
            'results_count' => 5,
        ]);

        // Create history for current user
        SearchHistory::create([
            'user_id' => $this->user->id,
            'query' => 'My Query',
            'filters' => [],
            'results_count' => 3,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/search/history');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('My Query', $data[0]['query']);
    }
}
