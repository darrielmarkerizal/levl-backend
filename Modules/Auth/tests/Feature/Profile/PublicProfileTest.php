<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can view public profile', function () {
    $userA = User::factory()->create(); 
    $userB = User::factory()->create();
    $token = auth()->login($userB);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson("/api/v1/users/{$userA->id}/profile");

    $response->assertStatus(200);
});

test('view profile fails without authentication', function () {
    $user = User::factory()->create();
    $response = $this->getJson("/api/v1/users/{$user->id}/profile");
    $response->assertStatus(401); 
});

test('owner can view own profile', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->getJson("/api/v1/users/{$user->id}/profile");

    $response->assertStatus(200);
});
