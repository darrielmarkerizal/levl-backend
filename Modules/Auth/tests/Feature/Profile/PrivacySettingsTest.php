<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can get privacy settings', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson('/api/v1/profile/privacy');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'profile_visibility',
                'show_email',
            ]
        ]);
});

test('user can update privacy settings', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile/privacy', [
            'profile_visibility' => 'private',
            'show_email' => true,
        ]);

    $response->assertStatus(200);
    // expect($user->fresh()->privacySettings->profile_visibility)->toBe('private');
});

test('privacy settings validate input', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile/privacy', [
            'profile_visibility' => 'invalid',
        ]);

    $response->assertStatus(422);
});
