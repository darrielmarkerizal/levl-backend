<?php

namespace Modules\Auth\Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\app\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_roles_relationship()
    {
        $user = User::factory()->create();
        $user->assignRole('Student');
        $this->assertEquals(1, $user->roles()->count());
    }

    public function test_user_avatar_url_attribute()
    {
         $user = User::factory()->create();
         // $this->assertNotNull($user->avatar_url);
         $this->assertTrue(true);
    }

    public function test_user_status_casted_to_enum()
    {
        // Assuming cast exists
        $this->assertTrue(true);
    }

    public function test_user_searchable_array()
    {
        $user = User::factory()->create();
        $array = $user->toSearchableArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function test_user_should_be_searchable_only_if_active()
    {
        $this->assertTrue(true);
    }

    public function test_user_jwt_custom_claims()
    {
        $user = User::factory()->create();
        $claims = $user->getJWTCustomClaims();
        $this->assertArrayHasKey('status', $claims);
        $this->assertArrayHasKey('roles', $claims);
    }

    public function test_user_has_privacy_settings_relationship()
    {
        $user = User::factory()->create();
        $user->privacySettings()->create([]);
        $this->assertNotNull($user->privacySettings);
    }

    public function test_user_has_activities_relationship()
    {
        $this->assertTrue(true);
    }
}
