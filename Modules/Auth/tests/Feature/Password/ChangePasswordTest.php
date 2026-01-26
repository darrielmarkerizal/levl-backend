<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

test('user can change password', function () {
    Event::fake(); 

    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertStatus(200);
    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('change password fails with wrong current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!')
    ]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'WrongPassword',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);
});

test('change password fails with weak new password', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);
    $token = auth()->login($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'OldPassword123!',
            'new_password' => 'weak',
            'new_password_confirmation' => 'weak',
        ]);

    $response->assertStatus(422);
});

test('change password fails without auth', function () {
    $response = $this->putJson('/api/v1/profile/password', []);
    $response->assertStatus(401);
});
