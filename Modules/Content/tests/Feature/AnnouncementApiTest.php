<?php

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Schemes\Models\Course;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementApiTest extends TestCase
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
    public function admin_can_create_announcement_with_valid_data()
    {
        $data = [
            'title' => 'Test Announcement',
            'content' => 'This is a test announcement content.',
            'target_type' => 'all',
            'priority' => 'normal',
            'status' => 'draft',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dibuat.',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content', 'status'],
            ]);

        $this->assertDatabaseHas('announcements', [
            'title' => 'Test Announcement',
            'content' => 'This is a test announcement content.',
        ]);
    }

    /** @test */
    public function cannot_create_announcement_without_title()
    {
        $data = [
            'content' => 'This is a test announcement content.',
            'target_type' => 'all',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function cannot_create_announcement_without_content()
    {
        $data = [
            'title' => 'Test Announcement',
            'target_type' => 'all',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function cannot_create_announcement_with_invalid_target_type()
    {
        $data = [
            'title' => 'Test Announcement',
            'content' => 'Content',
            'target_type' => 'invalid_type',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_type']);
    }

    /** @test */
    public function cannot_create_announcement_with_invalid_priority()
    {
        $data = [
            'title' => 'Test Announcement',
            'content' => 'Content',
            'target_type' => 'all',
            'priority' => 'invalid_priority',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    /** @test */
    public function cannot_create_announcement_with_past_scheduled_date()
    {
        $data = [
            'title' => 'Test Announcement',
            'content' => 'Content',
            'target_type' => 'all',
            'scheduled_at' => now()->subDay()->toIso8601String(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function student_cannot_create_announcement()
    {
        $token = auth('api')->login($this->student);

        $data = [
            'title' => 'Test Announcement',
            'content' => 'Content',
            'target_type' => 'all',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/announcements', $data);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_get_all_announcements()
    {
        Announcement::factory()->published()->count(5)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'content', 'status'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_get_announcement_detail()
    {
        $announcement = Announcement::factory()->published()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                ],
            ]);
    }

    /** @test */
    public function cannot_get_nonexistent_announcement()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/announcements/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_update_announcement()
    {
        $announcement = Announcement::factory()->create(['author_id' => $this->admin->id]);

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson("/api/v1/announcements/{$announcement->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman berhasil diperbarui.',
            ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);
    }

    /** @test */
    public function admin_can_delete_announcement()
    {
        $announcement = Announcement::factory()->create(['author_id' => $this->admin->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/v1/announcements/{$announcement->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dihapus.',
            ]);

        $this->assertSoftDeleted('announcements', ['id' => $announcement->id]);
    }

    /** @test */
    public function admin_can_publish_announcement()
    {
        $announcement = Announcement::factory()->create([
            'author_id' => $this->admin->id,
            'status' => 'draft',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/announcements/{$announcement->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dipublikasikan.',
            ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'status' => 'published',
        ]);

        $this->assertNotNull($announcement->fresh()->published_at);
    }

    /** @test */
    public function admin_can_schedule_announcement()
    {
        $announcement = Announcement::factory()->create([
            'author_id' => $this->admin->id,
            'status' => 'draft',
        ]);

        $scheduledAt = now()->addDays(2)->toIso8601String();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/announcements/{$announcement->id}/schedule", [
            'scheduled_at' => $scheduledAt,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dijadwalkan.',
            ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'status' => 'scheduled',
        ]);
    }

    /** @test */
    public function user_can_mark_announcement_as_read()
    {
        $announcement = Announcement::factory()->published()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson("/api/v1/announcements/{$announcement->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengumuman ditandai sudah dibaca.',
            ]);

        $this->assertDatabaseHas('content_reads', [
            'user_id' => $this->admin->id,
            'readable_type' => Announcement::class,
            'readable_id' => $announcement->id,
        ]);
    }

    /** @test */
    public function unauthenticated_user_can_access_public_announcements()
    {
        // Public announcements should be accessible without auth
        $response = $this->getJson('/api/v1/announcements');

        // If route requires auth, expect 401; if public, expect 200
        $response->assertStatus(200);
    }

    /** @test */
    public function can_filter_announcements_by_priority()
    {
        Announcement::factory()->published()->highPriority()->count(2)->create();
        Announcement::factory()->published()->count(3)->create(['priority' => 'normal']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/announcements?priority=high');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function can_filter_announcements_by_course()
    {
        $course = Course::factory()->create();
        // Create announcements with target_type='all' so they're visible to all users
        Announcement::factory()->published()->count(2)->create([
            'course_id' => $course->id,
            'target_type' => 'all',
        ]);
        Announcement::factory()->published()->count(3)->create([
            'course_id' => null,
            'target_type' => 'all',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson("/api/v1/announcements?course_id={$course->id}");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }
}
