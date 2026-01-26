<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('admin can bulk deactivate users', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    
    $users = User::factory()->count(3)->create(['status' => 'active']);
    $ids = $users->pluck('id')->toArray();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($admin)])
        ->postJson('/api/v1/users/bulk/deactivate', [
            'user_ids' => $ids
        ]);

    $response->assertStatus(200)
        ->assertJson(['deactivated_count' => 3]);
        
    expect(User::whereIn('id', $ids)->where('status', 'inactive')->count())->toBe(3);
});
