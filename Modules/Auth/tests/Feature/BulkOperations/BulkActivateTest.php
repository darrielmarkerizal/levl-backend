<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('admin can bulk activate users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $users = User::factory()->count(3)->create(['status' => 'inactive']);
    $ids = $users->pluck('id')->toArray();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->postJson('/api/v1/users/bulk/activate', [
            'user_ids' => $ids
        ]);

    $response->assertStatus(200)
        ->assertJson(['activated_count' => 3]);
        
    expect(User::whereIn('id', $ids)->where('status', 'active')->count())->toBe(3);
});
