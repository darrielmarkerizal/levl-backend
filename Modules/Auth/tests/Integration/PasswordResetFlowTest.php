<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

test('full password reset flow', function () {
    // 1. Forgot password
    $user = User::factory()->create(['email' => 'reset@example.com', 'password' => Hash::make('OldPass')]);
    
    $this->postJson('/api/v1/auth/password/forgot', ['login' => 'reset@example.com'])
         ->assertStatus(200);
         
    // 2. Receive email (Mock token)
    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->where('email', 'reset@example.com')->update([
        'token' => Hash::make($plainToken)
    ]);
    
    // 3. Reset password
     $this->postJson('/api/v1/auth/password/forgot/confirm', [
        'token' => $plainToken,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertStatus(200);
    
    // 4. Login with new password
    $this->postJson('/api/v1/auth/login', [
        'login' => 'reset@example.com',
        'password' => 'NewPassword123!',
    ])->assertStatus(200);
});
