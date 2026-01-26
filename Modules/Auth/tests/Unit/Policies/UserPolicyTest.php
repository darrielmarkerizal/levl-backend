<?php

use Modules\Auth\app\Models\User;
use Modules\Auth\app\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can view any', function () {
    $user = User::factory()->create();
    $user->assignRole('Superadmin');
    
    $policy = new UserPolicy();
    expect($policy->viewAny($user))->toBeTrue();
});

test('student cannot view any', function () {
    $user = User::factory()->create();
    $user->assignRole('Student');
    
    $policy = new UserPolicy();
    expect($policy->viewAny($user))->toBeFalse();
});
