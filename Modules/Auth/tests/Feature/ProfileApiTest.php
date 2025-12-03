<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_can_get_own_profile(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
            ]);
    }

    public function test_can_update_profile(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
                'bio' => 'This is my bio',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'bio' => 'This is my bio',
        ]);
    }

    public function test_cannot_update_profile_with_invalid_email(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile', [
                'email' => 'invalid-email',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_upload_avatar(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar uploaded successfully.',
            ]);
    }

    public function test_cannot_upload_non_image_file_as_avatar(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_avatar(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/v1/profile/avatar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Avatar deleted successfully.',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }
}
