<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can logout', function () {
    $user = User::factory()->create(['status' => 'active']);
    $token = auth()->login($user); 
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out']);
});

test('logout invalidates token', function () {
    $user = User::factory()->create(['status' => 'active']);
    $token = auth()->login($user); 
    
    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/logout');

    // Try accessing profile
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson('/api/v1/profile');
        
    $response->assertStatus(401);
});

test('logout fails without auth', function () {
    $response = $this->postJson('/api/v1/auth/logout');
    $response->assertStatus(401);
});
