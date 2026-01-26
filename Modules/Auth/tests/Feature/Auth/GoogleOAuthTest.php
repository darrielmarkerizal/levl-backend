<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

test('user can redirect to google', function () {
    $response = $this->getJson('/api/v1/auth/google/redirect');
    $response->assertStatus(200) // Or 302
             ->assertJsonStructure(['url']);
});

test('user can handle google callback', function () {
    Socialite::shouldReceive('driver->stateless->user')->andReturn((object)[
        'id' => '12345',
        'name' => 'Google User',
        'email' => 'google@example.com',
        'token' => 'token',
    ]);
    
    $response = $this->getJson('/api/v1/auth/google/callback');
    $response->assertStatus(200);
    
    $this->assertDatabaseHas('users', ['email' => 'google@example.com']);
});
