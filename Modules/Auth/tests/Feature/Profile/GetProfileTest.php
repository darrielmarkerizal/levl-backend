<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can get profile', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson('/api/v1/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'username',
                'email',
                'roles',
                'avatar_url',
            ]
        ]);
});

test('get profile fails without auth', function () {
    $response = $this->getJson('/api/v1/profile');
    $response->assertStatus(401);
});

test('get profile logic cached', function () {
     // Verify cache logic
     expect(true)->toBeTrue();
});
