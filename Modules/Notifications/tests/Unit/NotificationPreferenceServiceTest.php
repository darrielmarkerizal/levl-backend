<?php

namespace Modules\Notifications\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Services\NotificationPreferenceService;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationPreferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationPreferenceService;
    }

    public function test_get_preferences_creates_defaults_for_new_user(): void
    {
        $user = User::factory()->create();

        $preferences = $this->service->getPreferences($user);

        $this->assertNotEmpty($preferences);
        $this->assertGreaterThan(0, $preferences->count());

        // Check that all categories and channels are covered
        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $expectedCount = count($categories) * count($channels);

        $this->assertEquals($expectedCount, $preferences->count());
    }

    public function test_update_preferences_saves_correctly(): void
    {
        $user = User::factory()->create();

        $preferencesData = [
            [
                'category' => NotificationPreference::CATEGORY_COURSE_UPDATES,
                'channel' => NotificationPreference::CHANNEL_EMAIL,
                'enabled' => false,
                'frequency' => NotificationPreference::FREQUENCY_DAILY,
            ],
            [
                'category' => NotificationPreference::CATEGORY_ASSIGNMENTS,
                'channel' => NotificationPreference::CHANNEL_IN_APP,
                'enabled' => true,
                'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
            ],
        ];

        $result = $this->service->updatePreferences($user, $preferencesData);

        $this->assertTrue($result);

        // Verify preferences were saved
        $savedPreference = NotificationPreference::where('user_id', $user->id)
            ->where('category', NotificationPreference::CATEGORY_COURSE_UPDATES)
            ->where('channel', NotificationPreference::CHANNEL_EMAIL)
            ->first();

        $this->assertNotNull($savedPreference);
        $this->assertFalse($savedPreference->enabled);
        $this->assertEquals(NotificationPreference::FREQUENCY_DAILY, $savedPreference->frequency);
    }

    public function test_should_send_notification_respects_preferences(): void
    {
        $user = User::factory()->create();

        // Create a preference that disables email notifications for course updates
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_COURSE_UPDATES,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $shouldSend = $this->service->shouldSendNotification(
            $user,
            NotificationPreference::CATEGORY_COURSE_UPDATES,
            NotificationPreference::CHANNEL_EMAIL
        );

        $this->assertFalse($shouldSend);
    }

    public function test_should_send_notification_returns_true_when_enabled(): void
    {
        $user = User::factory()->create();

        // Create a preference that enables in-app notifications for assignments
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_ASSIGNMENTS,
            'channel' => NotificationPreference::CHANNEL_IN_APP,
            'enabled' => true,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $shouldSend = $this->service->shouldSendNotification(
            $user,
            NotificationPreference::CATEGORY_ASSIGNMENTS,
            NotificationPreference::CHANNEL_IN_APP
        );

        $this->assertTrue($shouldSend);
    }

    public function test_critical_notifications_always_sent(): void
    {
        $user = User::factory()->create();

        // Create a preference that disables system notifications
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_SYSTEM,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        // System notifications are critical and should always be sent
        $shouldSend = $this->service->shouldSendNotification(
            $user,
            NotificationPreference::CATEGORY_SYSTEM,
            NotificationPreference::CHANNEL_EMAIL
        );

        $this->assertTrue($shouldSend);
    }

    public function test_assessment_notifications_are_critical(): void
    {
        $user = User::factory()->create();

        // Create a preference that disables assessment notifications
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_ASSESSMENTS,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        // Assessment notifications are critical and should always be sent
        $shouldSend = $this->service->shouldSendNotification(
            $user,
            NotificationPreference::CATEGORY_ASSESSMENTS,
            NotificationPreference::CHANNEL_EMAIL
        );

        $this->assertTrue($shouldSend);
    }

    public function test_reset_to_defaults_clears_and_recreates_preferences(): void
    {
        $user = User::factory()->create();

        // Create some custom preferences
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_FORUM,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_WEEKLY,
        ]);

        $result = $this->service->resetToDefaults($user);

        $this->assertTrue($result);

        // Verify all default preferences were created
        $preferences = NotificationPreference::where('user_id', $user->id)->get();
        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $expectedCount = count($categories) * count($channels);

        $this->assertEquals($expectedCount, $preferences->count());
    }

    public function test_get_default_preferences_returns_all_combinations(): void
    {
        $defaults = $this->service->getDefaultPreferences();

        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $expectedCount = count($categories) * count($channels);

        $this->assertCount($expectedCount, $defaults);

        // Verify structure
        foreach ($defaults as $default) {
            $this->assertArrayHasKey('category', $default);
            $this->assertArrayHasKey('channel', $default);
            $this->assertArrayHasKey('enabled', $default);
            $this->assertArrayHasKey('frequency', $default);
        }
    }

    public function test_should_send_uses_defaults_when_no_preference_exists(): void
    {
        $user = User::factory()->create();

        // Don't create any preferences, should use defaults
        $shouldSend = $this->service->shouldSendNotification(
            $user,
            NotificationPreference::CATEGORY_ASSIGNMENTS,
            NotificationPreference::CHANNEL_EMAIL
        );

        // Assignments via email should be enabled by default
        $this->assertTrue($shouldSend);
    }
}
