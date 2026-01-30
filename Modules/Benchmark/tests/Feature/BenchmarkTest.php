<?php

namespace Modules\Benchmark\tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;

class BenchmarkTest extends TestCase
{
    // usage of RefreshDatabase might be needed if consistent DB state is required,
    // but for benchmark logic checking, we might just need some data.
    // Given the project uses RefreshDatabase in base TestCase or similar, I'll rely on environment.
    
    public function test_baseline_endpoint_returns_ok()
    {
        $response = $this->getJson('/api/benchmark/baseline');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'type' => 'baseline',
                 ]);
    }

    public function test_light_endpoint_returns_ok()
    {
        // Ensure at least one user exists
        if (User::count() === 0) {
           User::factory()->create();
        }

        $response = $this->getJson('/api/benchmark/light');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'type' => 'light',
                 ]);
    }

    public function test_heavy_endpoint_returns_ok()
    {
        // Ensure we have some users
        if (User::count() < 10) {
            User::factory()->count(10)->create();
        }

        $response = $this->getJson('/api/benchmark/heavy');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'type' => 'heavy',
                 ]);
    }
}
