<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\NotificationPreference;
use Tests\TestCase;

class NotificationPreferenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authenticated tests
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    /** @test */
    public function authenticated_user_can_get_notification_preferences()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/notification-preferences');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'category',
                        'channel',
                        'enabled',
                        'frequency',
                    ],
                ],
                'meta' => [
                    'categories',
                    'channels',
                    'frequencies',
                ],
            ]);

        // Verify meta contains expected values
        $meta = $response->json('meta');
        $this->assertContains('course_updates', $meta['categories']);
        $this->assertContains('email', $meta['channels']);
        $this->assertContains('immediate', $meta['frequencies']);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_notification_preferences()
    {
        $response = $this->getJson('/api/v1/notification-preferences');

        $response->assertStatus(401);
    }

    /** @test */
    public function get_preferences_creates_defaults_if_none_exist()
    {
        // Ensure no preferences exist
        $this->assertDatabaseMissing('notification_preferences', [
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/notification-preferences');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify defaults were created
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
        ]);

        // Should have preferences for all category/channel combinations
        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $expectedCount = count($categories) * count($channels);

        $this->assertCount($expectedCount, $response->json('data'));
    }

    /** @test */
    public function authenticated_user_can_update_notification_preferences()
    {
        // First get the preferences to ensure they exist
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/notification-preferences');

        $preferences = [
            [
                'category' => 'course_updates',
                'channel' => 'email',
                'enabled' => false,
                'frequency' => 'daily',
            ],
            [
                'category' => 'assignments',
                'channel' => 'in_app',
                'enabled' => true,
                'frequency' => 'immediate',
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => $preferences,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        // Verify preferences were updated in database
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'category' => 'course_updates',
            'channel' => 'email',
            'enabled' => false,
            'frequency' => 'daily',
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'category' => 'assignments',
            'channel' => 'in_app',
            'enabled' => true,
            'frequency' => 'immediate',
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_update_notification_preferences()
    {
        $preferences = [
            [
                'category' => 'course_updates',
                'channel' => 'email',
                'enabled' => false,
                'frequency' => 'daily',
            ],
        ];

        $response = $this->putJson('/api/v1/notification-preferences', [
            'preferences' => $preferences,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function update_preferences_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'category' => 'course_updates',
                    // Missing channel, enabled, frequency
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences.0.channel', 'preferences.0.enabled', 'preferences.0.frequency']);
    }

    /** @test */
    public function update_preferences_validates_category_values()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'category' => 'invalid_category',
                    'channel' => 'email',
                    'enabled' => true,
                    'frequency' => 'immediate',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences.0.category']);
    }

    /** @test */
    public function update_preferences_validates_channel_values()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'category' => 'course_updates',
                    'channel' => 'invalid_channel',
                    'enabled' => true,
                    'frequency' => 'immediate',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences.0.channel']);
    }

    /** @test */
    public function update_preferences_validates_frequency_values()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'category' => 'course_updates',
                    'channel' => 'email',
                    'enabled' => true,
                    'frequency' => 'invalid_frequency',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences.0.frequency']);
    }

    /** @test */
    public function update_preferences_validates_enabled_is_boolean()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => [
                [
                    'category' => 'course_updates',
                    'channel' => 'email',
                    'enabled' => 'not_a_boolean',
                    'frequency' => 'immediate',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences.0.enabled']);
    }

    /** @test */
    public function authenticated_user_can_reset_preferences_to_defaults()
    {
        // First create some custom preferences
        NotificationPreference::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'category' => 'course_updates',
                'channel' => 'email',
            ],
            [
                'enabled' => false,
                'frequency' => 'weekly',
            ]
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/notification-preferences/reset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences reset to defaults successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        // Verify preferences were reset
        // Email should be enabled for important categories by default
        $preference = NotificationPreference::where('user_id', $this->user->id)
            ->where('category', 'assessments')
            ->where('channel', 'email')
            ->first();

        $this->assertNotNull($preference);
        $this->assertTrue($preference->enabled);
        $this->assertEquals('immediate', $preference->frequency);
    }

    /** @test */
    public function unauthenticated_user_cannot_reset_preferences()
    {
        $response = $this->postJson('/api/v1/notification-preferences/reset');

        $response->assertStatus(401);
    }

    /** @test */
    public function reset_preferences_deletes_old_and_creates_new_defaults()
    {
        // Create some preferences
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'category' => 'course_updates',
            'channel' => 'email',
            'enabled' => false,
            'frequency' => 'weekly',
        ]);

        $oldCount = NotificationPreference::where('user_id', $this->user->id)->count();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/notification-preferences/reset');

        $response->assertStatus(200);

        // Should have all default preferences
        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();
        $expectedCount = count($categories) * count($channels);

        $newCount = NotificationPreference::where('user_id', $this->user->id)->count();
        $this->assertEquals($expectedCount, $newCount);
    }

    /** @test */
    public function user_can_only_access_their_own_preferences()
    {
        // Create another user with preferences
        $otherUser = User::factory()->create();
        NotificationPreference::create([
            'user_id' => $otherUser->id,
            'category' => 'course_updates',
            'channel' => 'email',
            'enabled' => false,
            'frequency' => 'weekly',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->getJson('/api/v1/notification-preferences');

        $response->assertStatus(200);

        // Verify only current user's preferences are returned
        $data = $response->json('data');
        foreach ($data as $preference) {
            $this->assertEquals($this->user->id, $preference['user_id']);
        }
    }

    /** @test */
    public function update_preferences_only_affects_current_user()
    {
        // Create another user with preferences
        $otherUser = User::factory()->create();
        NotificationPreference::create([
            'user_id' => $otherUser->id,
            'category' => 'course_updates',
            'channel' => 'email',
            'enabled' => true,
            'frequency' => 'immediate',
        ]);

        // Update current user's preferences
        $preferences = [
            [
                'category' => 'course_updates',
                'channel' => 'email',
                'enabled' => false,
                'frequency' => 'daily',
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->putJson('/api/v1/notification-preferences', [
            'preferences' => $preferences,
        ]);

        $response->assertStatus(200);

        // Verify other user's preferences are unchanged
        $otherUserPreference = NotificationPreference::where('user_id', $otherUser->id)
            ->where('category', 'course_updates')
            ->where('channel', 'email')
            ->first();

        $this->assertTrue($otherUserPreference->enabled);
        $this->assertEquals('immediate', $otherUserPreference->frequency);
    }
}
