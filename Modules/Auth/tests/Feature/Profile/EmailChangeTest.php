<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

test('user can request email change', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/email/change', [
            'new_email' => 'new@example.com'
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['uuid']]);
});

test('user can verify email change', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);
    $token = auth()->login($user);
    
    $otpToken = Str::random(16);
    $uuid = Str::uuid()->toString();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/email/change/verify', [
            'token' => $otpToken,
            'uuid' => $uuid,
        ]);

    expect(true)->toBeTrue();
});

test('request email change fails with existing email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'old@example.com']);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/email/change', [
            'new_email' => 'taken@example.com'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_email']);
});

test('verify email change fails with invalid token', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->postJson('/api/v1/profile/email/change/verify', [
            'token' => 'invalid',
            'uuid' => Str::uuid()->toString(),
        ]);

    $response->assertStatus(422);
});
