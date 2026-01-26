<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can delete user', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $user = User::factory()->create();
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('superadmin cannot delete self', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->deleteJson("/api/v1/users/{$superadmin->id}");

    $response->assertStatus(422); // Or 403
});

test('admin cannot delete user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $user = User::factory()->create();
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(403);
});
