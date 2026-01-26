<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can restore deleted account', function () {
    $user = User::factory()->create(['status' => 'deleted', 'deleted_at' => now()->subDay()]);
    $token = auth()->login($user); 
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/account/restore');

    $response->assertStatus(200);
    expect($user->fresh()->status)->toBe('active');
    expect($user->fresh()->deleted_at)->toBeNull();
});

test('restore validates retention period', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->delete();
    $user->forceFill(['deleted_at' => now()->subDays(31)]); 
    $user->save();
    
    $token = auth()->login($user); 

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/account/restore');

    $response->assertStatus(422);
});

test('restore fails without auth', function () {
    $response = $this->postJson('/api/v1/profile/account/restore');
    $response->assertStatus(401);
});
