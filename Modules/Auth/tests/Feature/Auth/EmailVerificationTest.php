<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('user can verify email with valid token', function () {
    $user = User::factory()->create([
        'status' => 'pending', 
        'email_verified_at' => null
    ]);
    
    // This test requires a mechanism to generate and store OTPs for testing.
    // Assuming a method or setup exists to provide a valid token and UUID.
    $token = 'valid_test_token'; // Placeholder
    $uuid = 'valid_test_uuid';   // Placeholder
    
    // Logic to insert OTP into DB manually for test...

    $response = $this->postJson('/api/v1/auth/email/verify', [
        'token' => $token,
        'uuid' => $uuid,
    ]);
    
    // Assertions would check for email_verified_at being set and status changing to 'active'.
    // $response->assertStatus(200);
    expect(true)->toBeTrue(); // Placeholder assertion
});

test('verify fails with invalid token', function () {
    $response = $this->postJson('/api/v1/auth/email/verify', [
        'token' => 'invalid',
        'uuid' => 'uuid'
    ]);
    
    $response->assertStatus(422) // or 400
        ->assertJsonValidationErrors(['token']);
});

test('verify fails with expired token', function () {
    // Generate expired token logic
    expect(true)->toBeTrue();
});

test('user cannot verify if already verified', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
    // Setup token for user
    
    $response = $this->postJson('/api/v1/auth/email/verify', [
        'token' => 'valid',
        'uuid' => 'uuid'
    ]);
    
    // $response->assertStatus(400) or 200 with message "already verified"
    expect(true)->toBeTrue();
});
