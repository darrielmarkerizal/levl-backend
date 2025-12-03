<?php

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Content\Models\ContentCategory;
use Modules\Content\Models\News;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $instructor;

    protected User $student;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'instructor', 'guard_name' => 'api']);
        Role::create(['name' => 'student', 'guard_name' => 'api']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('instructor');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        // Get token for admin
        $this->token = auth('api')->login($this->admin);
    }

    /** @test */
    public function admin_can_create_news_with_valid_data()
    {
        $data = [
            'title' => 'Test News Article',
            'slug' => 'test-news-article',
            'excerpt' => 'This is a test excerpt.',
            'content' => 'This is the full content of the news article.',
            'status' => 'draft',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Berita berhasil dibuat.',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'slug', 'content'],
            ]);

        $this->assertDatabaseHas('news', [
            'title' => 'Test News Article',
            'slug' => 'test-news-article',
        ]);
    }

    /** @test */
    public function instructor_can_create_news()
    {
        $token = auth('api')->login($this->instructor);

        $data = [
            'title' => 'Instructor News',
            'content' => 'News content by instructor.',
            'status' => 'draft',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(201);
    }

    /** @test */
    public function student_cannot_create_news()
    {
        $token = auth('api')->login($this->student);

        $data = [
            'title' => 'Student News',
            'content' => 'News content by student.',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_create_news_without_title()
    {
        $data = [
            'content' => 'News content without title.',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function cannot_create_news_without_content()
    {
        $data = [
            'title' => 'News Title',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function cannot_create_news_with_duplicate_slug()
    {
        News::factory()->create(['slug' => 'duplicate-slug']);

        $data = [
            'title' => 'Another News',
            'slug' => 'duplicate-slug',
            'content' => 'Content',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function can_create_news_with_categories()
    {
        $categories = ContentCategory::factory()->count(2)->create();

        $data = [
            'title' => 'News with Categories',
            'content' => 'Content',
            'category_ids' => $categories->pluck('id')->toArray(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(201);

        $news = News::where('title', 'News with Categories')->first();
        $this->assertCount(2, $news->categories);
    }

    /** @test */
    public function cannot_create_news_with_invalid_category_ids()
    {
        $data = [
            'title' => 'News with Invalid Categories',
            'content' => 'Content',
            'category_ids' => [99999, 88888],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/news', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_ids.0', 'category_ids.1']);
    }

    /** @test */
    public function can_get_all_news()
    {
        News::factory()->published()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/news');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'slug', 'excerpt'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_get_news_detail_by_slug()
    {
        $news = News::factory()->published()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/v1/news/{$news->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $news->id,
                    'slug' => $news->slug,
                ],
            ]);
    }

    /** @test */
    public function viewing_news_increments_view_count()
    {
        $news = News::factory()->published()->create(['views_count' => 0]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/v1/news/{$news->slug}");

        $this->assertEquals(1, $news->fresh()->views_count);
    }

    /** @test */
    public function cannot_get_nonexistent_news()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/news/nonexistent-slug');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_update_news()
    {
        $news = News::factory()->create(['author_id' => $this->admin->id]);

        $data = [
            'title' => 'Updated News Title',
            'content' => 'Updated content',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/v1/news/{$news->slug}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Berita berhasil diperbarui.',
            ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'Updated News Title',
        ]);
    }

    /** @test */
    public function admin_can_delete_news()
    {
        $news = News::factory()->create(['author_id' => $this->admin->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/v1/news/{$news->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Berita berhasil dihapus.',
            ]);

        $this->assertSoftDeleted('news', ['id' => $news->id]);
    }

    /** @test */
    public function admin_can_publish_news()
    {
        $news = News::factory()->create([
            'author_id' => $this->admin->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/news/{$news->slug}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Berita berhasil dipublikasikan.',
            ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => 'published',
        ]);
    }

    /** @test */
    public function can_get_trending_news()
    {
        News::factory()->published()->count(5)->create([
            'views_count' => 100,
            'published_at' => now()->subHours(5),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/news/trending');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'views_count'],
                ],
            ]);
    }

    /** @test */
    public function can_filter_news_by_category()
    {
        $category = ContentCategory::factory()->create();
        $news1 = News::factory()->published()->create();
        $news2 = News::factory()->published()->create();

        $news1->categories()->attach($category->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/v1/news?category_id={$category->id}");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
    }

    /** @test */
    public function can_filter_featured_news()
    {
        News::factory()->published()->featured()->count(2)->create();
        News::factory()->published()->count(3)->create(['is_featured' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/news?featured=1');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function unauthenticated_user_can_access_public_news()
    {
        // Public news should be accessible without auth
        $response = $this->getJson('/api/v1/news');

        // If route requires auth, expect 401; if public, expect 200
        $response->assertStatus(200);
    }
}
