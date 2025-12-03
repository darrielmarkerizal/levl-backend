<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;
use Modules\Auth\Models\UserActivity;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->viewer = User::factory()->create();
    }

    public function test_user_has_privacy_settings_relationship(): void
    {
        // Privacy settings are auto-created by observer
        $this->assertInstanceOf(ProfilePrivacySetting::class, $this->user->privacySettings);
    }

    public function test_user_has_activities_relationship(): void
    {
        UserActivity::create([
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
            'activity_data' => [],
        ]);

        $this->assertCount(1, $this->user->activities);
    }

    public function test_scope_active_filters_active_users(): void
    {
        $activeUser = User::factory()->create(['account_status' => 'active']);
        $suspendedUser = User::factory()->create(['account_status' => 'suspended']);

        $activeUsers = User::active()->get();

        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($suspendedUser));
    }

    public function test_scope_suspended_filters_suspended_users(): void
    {
        $activeUser = User::factory()->create(['account_status' => 'active']);
        $suspendedUser = User::factory()->create(['account_status' => 'suspended']);

        $suspendedUsers = User::suspended()->get();

        $this->assertFalse($suspendedUsers->contains($activeUser));
        $this->assertTrue($suspendedUsers->contains($suspendedUser));
    }

    public function test_can_be_viewed_by_returns_true_for_owner(): void
    {
        $this->user->privacySettings->update([
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
        ]);

        $this->assertTrue($this->user->canBeViewedBy($this->user));
    }

    public function test_can_be_viewed_by_respects_privacy_settings(): void
    {
        $this->user->privacySettings->update([
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PRIVATE,
        ]);

        $this->assertFalse($this->user->canBeViewedBy($this->viewer));
    }

    public function test_get_visible_fields_for_returns_all_for_owner(): void
    {
        $fields = $this->user->getVisibleFieldsFor($this->user);

        $this->assertEquals(['*'], $fields);
    }

    public function test_log_activity_creates_activity_record(): void
    {
        $this->user->logActivity(UserActivity::TYPE_ENROLLMENT, ['course_id' => 1]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $this->user->id,
            'activity_type' => UserActivity::TYPE_ENROLLMENT,
        ]);
    }
}
