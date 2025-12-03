<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ProfilePrivacySettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->viewer = User::factory()->create();

        // Delete auto-created privacy settings for testing
        ProfilePrivacySetting::where('user_id', $this->user->id)->delete();
        ProfilePrivacySetting::where('user_id', $this->viewer->id)->delete();
    }

    public function test_can_create_privacy_settings(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => false,
            'show_phone' => false,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
        ]);

        $this->assertDatabaseHas('profile_privacy_settings', [
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
        ]);
    }

    public function test_is_public_returns_true_for_public_profile(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
        ]);

        $this->assertTrue($settings->isPublic());
    }

    public function test_is_public_returns_false_for_private_profile(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
        ]);

        $this->assertFalse($settings->isPublic());
    }

    public function test_can_show_field_returns_true_for_owner(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
            'show_email' => false,
        ]);

        $this->assertTrue($settings->canShowField('email', $this->user));
    }

    public function test_can_show_field_respects_privacy_settings(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => false,
        ]);

        $this->assertFalse($settings->canShowField('email', $this->viewer));
    }

    public function test_can_show_field_returns_false_for_private_profile(): void
    {
        $settings = ProfilePrivacySetting::create([
            'user_id' => $this->user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
            'show_email' => true,
        ]);

        $this->assertFalse($settings->canShowField('email', $this->viewer));
    }
}
