<?php

use Modules\Auth\app\Services\EmailVerificationService;
use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('send verification creates otp', function () {
    $service = app(EmailVerificationService::class);
    $user = User::factory()->create(['status' => 'pending']);
    
    $result = $service->sendVerificationLink($user);
    expect($result)->toBeString();
});
