<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can create admin', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->postJson('/api/v1/users', [
            'name' => 'New Admin',
            'username' => 'newadmin',
            'email' => 'admin@example.com',
            'role' => 'Admin'
        ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    
    $user = User::where('email', 'admin@example.com')->first();
    expect($user->hasRole('Admin'))->toBeTrue();
});

test('create user fails with duplicate email', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->postJson('/api/v1/users', [
            'name' => 'User',
            'username' => 'user',
            'email' => 'taken@example.com',
            'role' => 'Admin'
        ]);

    $response->assertStatus(422);
});

test('create user fails for student', function () {
    $student = User::factory()->create();
    $student->assignRole('Student');

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($student)])
        ->postJson('/api/v1/users', [
            'name' => 'User',
            'username' => 'user',
            'email' => 'new@example.com',
            'role' => 'Admin'
        ]);

    $response->assertStatus(403);
});
