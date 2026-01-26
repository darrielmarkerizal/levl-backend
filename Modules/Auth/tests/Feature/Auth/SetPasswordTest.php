<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

test('user can set password first time', function () {
    // Assuming 'is_password_set' column exists or is inferred
    $user = User::factory()->create(['password' => null]); 
    // Usually password null means not set.
    
    $token = auth()->login($user); // Assuming authenticated user can set password

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertStatus(200);
    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('user cannot set password if already set', function () {
    $user = User::factory()->create(['password' => Hash::make('pass')]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-password', [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertStatus(403);
});

test('set password validates complexity', function () {
    $user = User::factory()->create(['password' => null]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/auth/set-password', [
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

    $response->assertStatus(422);
});

test('set password fails without auth', function () {
    $response = $this->postJson('/api/v1/auth/set-password', []);
    $response->assertStatus(401);
});
