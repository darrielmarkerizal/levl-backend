<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can register with valid data', function () {
    $data = [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $response = $this->postJson('/api/v1/auth/register', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'user',
                'token',
                'verification_uuid',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'status' => 'pending', 
        'email_verified_at' => null,
    ]);
});

test('user cannot register with duplicate email', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Other User',
        'username' => 'other',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user cannot register with duplicate username', function () {
    User::factory()->create(['username' => 'existing']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'User',
        'username' => 'existing',
        'email' => 'new@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['username']);
});

test('user cannot register with weak password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'User',
        'username' => 'user',
        'email' => 'user@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('user cannot register with mismatch password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'User',
        'username' => 'user',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Mismatch123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('register requires all fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'username', 'email', 'password']);
});
