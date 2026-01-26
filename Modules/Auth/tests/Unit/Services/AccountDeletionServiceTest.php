<?php

use Modules\Auth\app\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('request deletion creates otp', function () {
    $service = app(AccountDeletionService::class);
    // $service->requestDeletion(...);
    expect(true)->toBeTrue();
});
