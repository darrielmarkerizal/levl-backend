<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can set username first time', function () {
    $user = User::factory()->create(['username' => null]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-username', [
            'username' => 'newusername'
        ]);

    $response->assertStatus(200);
    expect($user->fresh()->username)->toBe('newusername');
});

test('user cannot set username if already set', function () {
    $user = User::factory()->create(['username' => 'existing']);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-username', [
            'username' => 'newusername'
        ]);

    $response->assertStatus(403); // Forbidden
});

test('set username validates unique', function () {
    User::factory()->create(['username' => 'taken']);
    $user = User::factory()->create(['username' => null]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-username', [
            'username' => 'taken'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('set username validates format', function () {
    $user = User::factory()->create(['username' => null]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-username', [
            'username' => 'invalid username space'
        ]);

    $response->assertStatus(422);
});
