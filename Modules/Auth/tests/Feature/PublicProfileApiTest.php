<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Tests\TestCase;

class PublicProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'This is my bio',
        ]);

        $this->viewer = User::factory()->create();
    }

    public function test_can_view_public_profile(): void
    {
        $this->user->privacySettings->update([
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => true,
        ]);

        $response = $this->actingAs($this->viewer, 'api')
            ->getJson("/api/v1/users/{$this->user->id}/profile");

        // Response structure has user data nested
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify user data exists in response
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_cannot_view_private_profile(): void
    {
        $this->user->privacySettings->update([
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
        ]);

        $response = $this->actingAs($this->viewer, 'api')
            ->getJson("/api/v1/users/{$this->user->id}/profile");

        $response->assertStatus(403);
    }

    public function test_email_is_hidden_when_privacy_setting_is_false(): void
    {
        $this->user->privacySettings->update([
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => false,
        ]);

        $response = $this->actingAs($this->viewer, 'api')
            ->getJson("/api/v1/users/{$this->user->id}/profile");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_returns_404_for_non_existent_user(): void
    {
        $response = $this->actingAs($this->viewer, 'api')
            ->getJson('/api/v1/users/99999/profile');

        $response->assertStatus(404);
    }
}
