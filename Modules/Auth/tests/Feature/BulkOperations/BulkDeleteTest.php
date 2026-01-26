<?php

use Modules\Auth\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

test('superadmin can bulk delete users', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('Superadmin');
    
    $users = User::factory()->count(3)->create();
    $ids = $users->pluck('id')->toArray();

    $response = $this->withHeaders(['Authorization' => 'Bearer ' . auth()->login($superadmin)])
        ->deleteJson('/api/v1/users/bulk/delete', [
            'user_ids' => $ids
        ]);

    $response->assertStatus(200)
        ->assertJson(['deleted_count' => 3]);
        
    $this->assertSoftDeleted('users', ['id' => $ids[0]]);
});
