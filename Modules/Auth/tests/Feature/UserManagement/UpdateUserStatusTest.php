<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('admin can update user status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $user = User::factory()->create(['status' => 'active']);
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->putJson("/api/v1/users/{$user->id}", [
            'status' => 'inactive'
        ]);

    $response->assertStatus(200);
    expect($user->fresh()->status)->toBe('inactive');
});

test('update status fails to pending', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $user = User::factory()->create(['status' => 'active']);
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->putJson("/api/v1/users/{$user->id}", [
            'status' => 'pending'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('update status fails without auth', function () {
    $user = User::factory()->create(['status' => 'active']);
    $response = $this->putJson("/api/v1/users/{$user->id}", ['status' => 'inactive']);
    $response->assertStatus(401);
});
