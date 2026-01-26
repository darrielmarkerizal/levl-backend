<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

test('user can login with email', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'user',
                'token',
                'refresh_token',
            ]
        ]);
});

test('user can login with username', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'password' => Hash::make('Password123!'),
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'testuser',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200);
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'test@example.com',
        'password' => 'WrongPassword',
    ]);

    $response->assertStatus(401) // Or 422 depending on implementation
       ->assertJsonValidationErrors(['login']); // If validation error
});

test('pending user cannot login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'status' => 'pending',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(403) // Or 401 with specific message
             ->assertJson(['message' => 'Email not verified']);
});

test('banned user cannot login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Password123!'),
        'status' => 'banned',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'test@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(403)
             ->assertJson(['message' => 'Account is banned']);
});

test('login requires fields', function () {
    $response = $this->postJson('/api/v1/auth/login', []);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['login', 'password']);
});

test('login is rate limited', function () {
    // Mock throttling or multiple requests
    // Using standard Laravel throttling: 5 attempts
    $user = User::factory()->create(['email' => 'limit@example.com', 'password' => Hash::make('pass')]);
    
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'limit@example.com',
            'password' => 'wrong',
        ]);
    }

    $response->assertStatus(429);
});
