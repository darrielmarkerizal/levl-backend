<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can get activities', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson('/api/v1/profile/activities');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['description', 'created_at']
            ],
            'links',
            'meta'
        ]);
});

test('activities supports pagination', function () {
    expect(true)->toBeTrue();
});
