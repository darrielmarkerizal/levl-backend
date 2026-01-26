<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('registration flow', function () {
    $this->postJson('/api/v1/auth/register', [
         'name' => 'Reg User',
        'username' => 'reguser',
        'email' => 'reg@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(201);
});
