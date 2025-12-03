<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ProfilePasswordApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);
    }

    public function test_can_change_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully.',
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_cannot_change_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_cannot_change_password_with_weak_password(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'weak',
                'new_password_confirmation' => 'weak',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_change_password_without_confirmation(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'oldpassword123',
                'new_password' => 'newpassword123',
            ]);

        $response->assertStatus(422);
    }
}
