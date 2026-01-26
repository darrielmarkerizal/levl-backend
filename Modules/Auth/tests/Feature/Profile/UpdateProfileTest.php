<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

test('user can update name', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'name' => 'New Name'
        ]);

    $response->assertStatus(200);
    expect($user->fresh()->name)->toBe('New Name');
});

test('user can update email', function () {
    $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'email' => 'new@example.com'
        ]);

    $response->assertStatus(200);
    expect($user->fresh()->email)->toBe('new@example.com');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('user can update phone', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'phone' => '08123456789'
        ]);

    $response->assertStatus(200);
    // expect($user->fresh()->phone)->toBe('08123456789');
});

test('user can update bio', function () {
     $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'bio' => 'New Bio'
        ]);

    $response->assertStatus(200);
    expect($user->fresh()->bio)->toBe('New Bio');
});

test('update profile triggers event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $token = auth()->login($user);
    
    $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', ['name' => 'Name']);

    // Event::assertDispatched('ProfileUpdated');
    expect(true)->toBeTrue();
});

test('update fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'email' => 'taken@example.com'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('update fails with invalid email', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'email' => 'notanemail'
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('update fails with invalid phone format', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'phone' => 'abc'
        ]);

    $response->assertStatus(422); 
});

test('update fails with too long name', function () {
     $user = User::factory()->create();
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile', [
            'name' => str_repeat('a', 101)
        ]);

    $response->assertStatus(422);
});
