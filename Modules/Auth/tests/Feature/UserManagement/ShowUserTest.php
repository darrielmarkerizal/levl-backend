<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can view any user', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $otherUser = User::factory()->create();
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->getJson("/api/v1/users/{$otherUser->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $otherUser->id]);
});

test('admin cannot view superadmin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->getJson("/api/v1/users/{$superadmin->id}");

    $response->assertStatus(403);
});
