<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

test('user can reset password with valid token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com', 
        'password' => Hash::make('OldPassword123!')
    ]);
    
    $token = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/password/forgot/confirm', [
        'token' => $token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(200);
    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'test@example.com']);
});

test('reset fails with invalid token', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot/confirm', [
        'token' => 'invalid_token',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(422);
});

test('reset fails with expired token', function () {
    $token = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now()->subHours(2), 
    ]);

    $response = $this->postJson('/api/v1/auth/password/forgot/confirm', [
        'token' => $token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertStatus(422);
});
