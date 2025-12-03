<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ProfilePrivacyApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_get_privacy_settings(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/profile/privacy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'profile_visibility',
                    'show_email',
                    'show_phone',
                    'show_activity_history',
                    'show_achievements',
                    'show_statistics',
                ],
            ]);
    }

    public function test_can_update_privacy_settings(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/privacy', [
                'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
                'show_email' => false,
                'show_phone' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Privacy settings updated successfully.',
            ]);

        $this->assertDatabaseHas('profile_privacy_settings', [
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
            'show_email' => false,
        ]);
    }

    public function test_cannot_update_privacy_with_invalid_visibility(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/privacy', [
                'profile_visibility' => 'invalid_option',
            ]);

        $response->assertStatus(422);
    }

    public function test_privacy_settings_are_created_on_user_registration(): void
    {
        $newUser = User::factory()->create();

        $this->assertDatabaseHas('profile_privacy_settings', [
            'user_id' => $newUser->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
        ]);
    }
}
