<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can list all users', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    User::factory()->count(5)->create();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonCount(6, 'data'); 
});

test('list users supports pagination', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    User::factory()->count(20)->create();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->getJson('/api/v1/users?per_page=15');

    $response->assertStatus(200)
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure(['links', 'meta']);
});

test('list users supports search', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane key']);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->getJson('/api/v1/users?search=John');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'John Doe']);
});

test('list users fails for non-admin', function () {
    $student = User::factory()->create();
    $student->assignRole('Student');

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($student)])
        ->getJson('/api/v1/users');

    $response->assertStatus(403);
});
