<?php

namespace Modules\Forums\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class ThreadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();

        // Enroll user in course
        Enrollment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_create_thread()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/schemes/{$this->course->id}/forum/threads", [
                'title' => 'Test Thread',
                'content' => 'This is a test thread content.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'content', 'author_id'],
            ]);

        $this->assertDatabaseHas('threads', [
            'title' => 'Test Thread',
            'scheme_id' => $this->course->id,
            'author_id' => $this->user->id,
        ]);
    }

    public function test_user_can_view_thread_list()
    {
        Thread::factory()->count(5)->create([
            'scheme_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/schemes/{$this->course->id}/forum/threads");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'content', 'author_id'],
                    ],
                ],
            ]);
    }

    public function test_user_can_reply_to_thread()
    {
        $thread = Thread::factory()->create([
            'scheme_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
                'content' => 'This is a reply.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('replies', [
            'thread_id' => $thread->id,
            'author_id' => $this->user->id,
            'content' => 'This is a reply.',
        ]);
    }

    public function test_user_cannot_create_thread_in_unenrolled_scheme()
    {
        $otherCourse = Course::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/schemes/{$otherCourse->id}/forum/threads", [
                'title' => 'Test Thread',
                'content' => 'This is a test thread content.',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_thread()
    {
        $thread = Thread::factory()->create([
            'scheme_id' => $this->course->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/schemes/{$this->course->id}/forum/threads/{$thread->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_thread_increments_views_on_access()
    {
        $thread = Thread::factory()->create([
            'scheme_id' => $this->course->id,
            'views_count' => 0,
        ]);

        $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/schemes/{$this->course->id}/forum/threads/{$thread->id}");

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
            'views_count' => 1,
        ]);
    }

    public function test_closed_thread_prevents_new_replies()
    {
        $thread = Thread::factory()->closed()->create([
            'scheme_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/forum/threads/{$thread->id}/replies", [
                'content' => 'This should fail.',
            ]);

        $response->assertStatus(500);
    }
}
