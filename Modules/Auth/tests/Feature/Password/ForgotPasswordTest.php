<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can request password reset with email', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'login' => 'test@example.com'
    ]);

    $response->assertStatus(200);
});

test('user can request password reset with username', function () {
    $user = User::factory()->create(['username' => 'testuser', 'email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'login' => 'testuser'
    ]);

    $response->assertStatus(200);
});

test('forgot password fails without login field', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', []);
    $response->assertStatus(422);
});
