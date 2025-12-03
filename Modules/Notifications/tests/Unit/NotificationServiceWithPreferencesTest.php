<?php

namespace Modules\Notifications\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceWithPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    public function test_send_with_preferences_respects_disabled_preference(): void
    {
        $user = User::factory()->create();

        // Disable email notifications for course updates
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_COURSE_UPDATES,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $result = $this->service->sendWithPreferences(
            $user,
            NotificationPreference::CATEGORY_COURSE_UPDATES,
            NotificationPreference::CHANNEL_EMAIL,
            'Course Update',
            'New content available'
        );

        $this->assertNull($result);
    }

    public function test_send_with_preferences_sends_when_enabled(): void
    {
        $user = User::factory()->create();

        // Enable in-app notifications for assignments
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_ASSIGNMENTS,
            'channel' => NotificationPreference::CHANNEL_IN_APP,
            'enabled' => true,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $result = $this->service->sendWithPreferences(
            $user,
            NotificationPreference::CATEGORY_ASSIGNMENTS,
            NotificationPreference::CHANNEL_IN_APP,
            'New Assignment',
            'You have a new assignment'
        );

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('New Assignment', $result->title);
        $this->assertEquals(NotificationPreference::CATEGORY_ASSIGNMENTS, $result->type);
    }

    public function test_critical_notifications_bypass_preferences(): void
    {
        $user = User::factory()->create();

        // Disable system notifications
        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_SYSTEM,
            'channel' => NotificationPreference::CHANNEL_EMAIL,
            'enabled' => false,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        // Send critical notification
        $result = $this->service->sendWithPreferences(
            $user,
            NotificationPreference::CATEGORY_SYSTEM,
            NotificationPreference::CHANNEL_EMAIL,
            'Critical System Alert',
            'Important system message',
            null,
            true // isCritical
        );

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals('Critical System Alert', $result->title);
    }

    public function test_notification_includes_channel_information(): void
    {
        $user = User::factory()->create();

        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_FORUM,
            'channel' => NotificationPreference::CHANNEL_IN_APP,
            'enabled' => true,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $result = $this->service->sendWithPreferences(
            $user,
            NotificationPreference::CATEGORY_FORUM,
            NotificationPreference::CHANNEL_IN_APP,
            'Forum Reply',
            'Someone replied to your post'
        );

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals(NotificationPreference::CHANNEL_IN_APP, $result->channel);
    }

    public function test_notification_with_data_payload(): void
    {
        $user = User::factory()->create();

        NotificationPreference::create([
            'user_id' => $user->id,
            'category' => NotificationPreference::CATEGORY_ACHIEVEMENTS,
            'channel' => NotificationPreference::CHANNEL_IN_APP,
            'enabled' => true,
            'frequency' => NotificationPreference::FREQUENCY_IMMEDIATE,
        ]);

        $data = [
            'achievement_id' => 123,
            'achievement_name' => 'Course Completed',
            'points' => 100,
        ];

        $result = $this->service->sendWithPreferences(
            $user,
            NotificationPreference::CATEGORY_ACHIEVEMENTS,
            NotificationPreference::CHANNEL_IN_APP,
            'Achievement Unlocked',
            'You earned a new achievement!',
            $data
        );

        $this->assertInstanceOf(Notification::class, $result);
        $this->assertEquals($data, $result->data);
    }
}
