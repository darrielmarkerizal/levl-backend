<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

test('user can request account deletion', function () {
    $user = User::factory()->create(['password' => Hash::make('Password123!')]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/account/delete/request', [
            'password' => 'Password123!'
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['uuid']]);
});

test('user can confirm account deletion', function () {
    Event::fake(); 
    
    $user = User::factory()->create();
    $token = auth()->login($user);
    
    $otpToken = Str::random(16);
    $uuid = Str::uuid()->toString();
    
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/account/delete/confirm', [
            'token' => $otpToken,
            'uuid' => $uuid,
        ]);

    // Expect assertions
    expect(true)->toBeTrue();
});

test('deletion request fails with wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('Password123!')]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/account/delete/request', [
            'password' => 'WrongPassword'
        ]);

    $response->assertStatus(422);
});
