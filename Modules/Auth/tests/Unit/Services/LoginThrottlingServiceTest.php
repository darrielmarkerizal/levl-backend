<?php

use Modules\Auth\app\Services\LoginThrottlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('hit attempt increments counter', function () {
    $service = app(LoginThrottlingService::class);
    $key = 'test_key';
    
    $service->hitAttempt($key);
    expect($service->attempts($key))->toBe(1);
});

test('too many attempts returns true', function () {
    $service = app(LoginThrottlingService::class);
    $key = 'test_key';
    
    for ($i = 0; $i < 5; $i++) {
        $service->hitAttempt($key);
    }
    
    expect($service->tooManyAttempts($key))->toBeTrue();
});
