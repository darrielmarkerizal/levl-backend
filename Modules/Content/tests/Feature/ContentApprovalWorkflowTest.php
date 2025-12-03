<?php

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Models\User;
use Modules\Content\Events\ContentApproved;
use Modules\Content\Events\ContentRejected;
use Modules\Content\Events\ContentSubmitted;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $reviewer;

    protected User $author;

    protected string $adminToken;

    protected string $reviewerToken;

    protected string $authorToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'reviewer', 'guard_name' => 'api']);
        Role::create(['name' => 'instructor', 'guard_name' => 'api']);
        Role::create(['name' => 'student', 'guard_name' => 'api']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('reviewer');

        $this->author = User::factory()->create();
        $this->author->assignRole('instructor');

        // Get tokens
        $this->adminToken = auth('api')->login($this->admin);
        $this->reviewerToken = auth('api')->login($this->reviewer);
        $this->authorToken = auth('api')->login($this->author);
    }

    /** @test */
    public function can_submit_news_for_review()
    {
        Event::fake([ContentSubmitted::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->authorToken,
        ])->postJson("/api/v1/content/news/{$news->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => 'submitted',
        ]);

        // Check workflow history was created
        $this->assertDatabaseHas('content_workflow_history', [
            'content_type' => News::class,
            'content_id' => $news->id,
            'from_state' => 'draft',
            'to_state' => 'submitted',
            'user_id' => $this->author->id,
        ]);

        Event::assertDispatched(ContentSubmitted::class);
    }

    /** @test */
    public function can_submit_announcement_for_review()
    {
        Event::fake([ContentSubmitted::class]);

        $announcement = Announcement::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->authorToken,
        ])->postJson("/api/v1/content/announcement/{$announcement->id}/submit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'status' => 'submitted',
        ]);

        Event::assertDispatched(ContentSubmitted::class);
    }

    /** @test */
    public function reviewer_can_approve_content()
    {
        Event::fake([ContentApproved::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/approve", [
            'note' => 'Looks good!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => 'approved',
        ]);

        // Check workflow history was created with note
        $this->assertDatabaseHas('content_workflow_history', [
            'content_type' => News::class,
            'content_id' => $news->id,
            'from_state' => 'submitted',
            'to_state' => 'approved',
            'user_id' => $this->reviewer->id,
            'note' => 'Looks good!',
        ]);

        Event::assertDispatched(ContentApproved::class);
    }

    /** @test */
    public function reviewer_can_reject_content_with_reason()
    {
        Event::fake([ContentRejected::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/reject", [
            'reason' => 'Content needs more detail and better formatting.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'status' => 'rejected',
        ]);

        // Check workflow history was created with reason
        $this->assertDatabaseHas('content_workflow_history', [
            'content_type' => News::class,
            'content_id' => $news->id,
            'from_state' => 'submitted',
            'to_state' => 'rejected',
            'user_id' => $this->reviewer->id,
            'note' => 'Content needs more detail and better formatting.',
        ]);

        Event::assertDispatched(ContentRejected::class);
    }

    /** @test */
    public function reject_requires_reason()
    {
        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/reject", [
            // Missing reason
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function cannot_submit_already_submitted_content()
    {
        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->authorToken,
        ])->postJson("/api/v1/content/news/{$news->id}/submit");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function cannot_approve_draft_content()
    {
        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/approve");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function can_get_pending_review_content()
    {
        // Create content in various states
        $submittedNews = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
            'title' => 'Submitted News',
        ]);

        $inReviewNews = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'in_review',
            'title' => 'In Review News',
        ]);

        $draftNews = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'draft',
            'title' => 'Draft News',
        ]);

        $submittedAnnouncement = Announcement::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
            'title' => 'Submitted Announcement',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->getJson('/api/v1/content/pending-review');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.count', 3);

        // Should include submitted and in_review, but not draft
        $pendingContent = $response->json('data.pending_content');
        $titles = array_column($pendingContent, 'title');

        $this->assertContains('Submitted News', $titles);
        $this->assertContains('In Review News', $titles);
        $this->assertContains('Submitted Announcement', $titles);
        $this->assertNotContains('Draft News', $titles);
    }

    /** @test */
    public function can_filter_pending_review_by_type()
    {
        News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        Announcement::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        // Filter for news only
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->getJson('/api/v1/content/pending-review?type=news');

        $response->assertStatus(200);

        $pendingContent = $response->json('data.pending_content');
        $this->assertCount(1, $pendingContent);
        $this->assertEquals('news', $pendingContent[0]['type']);
    }

    /** @test */
    public function notifications_are_sent_when_content_submitted()
    {
        Event::fake([ContentSubmitted::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'draft',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->authorToken,
        ])->postJson("/api/v1/content/news/{$news->id}/submit");

        Event::assertDispatched(ContentSubmitted::class, function ($event) use ($news) {
            return $event->content->id === $news->id &&
                   $event->user->id === $this->author->id;
        });
    }

    /** @test */
    public function notifications_are_sent_when_content_approved()
    {
        Event::fake([ContentApproved::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/approve");

        Event::assertDispatched(ContentApproved::class, function ($event) use ($news) {
            return $event->content->id === $news->id &&
                   $event->user->id === $this->reviewer->id;
        });
    }

    /** @test */
    public function notifications_are_sent_when_content_rejected()
    {
        Event::fake([ContentRejected::class]);

        $news = News::factory()->create([
            'author_id' => $this->author->id,
            'status' => 'submitted',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson("/api/v1/content/news/{$news->id}/reject", [
            'reason' => 'Needs improvement',
        ]);

        Event::assertDispatched(ContentRejected::class, function ($event) use ($news) {
            return $event->content->id === $news->id &&
                   $event->user->id === $this->reviewer->id;
        });
    }

    /** @test */
    public function returns_404_for_nonexistent_content()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson('/api/v1/content/news/99999/approve');

        $response->assertStatus(404);
    }

    /** @test */
    public function returns_404_for_invalid_content_type()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->reviewerToken,
        ])->postJson('/api/v1/content/invalid_type/1/approve');

        $response->assertStatus(404);
    }
}
