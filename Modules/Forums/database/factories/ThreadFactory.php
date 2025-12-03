<?php

namespace Modules\Forums\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Thread;
use Modules\Schemes\Models\Course;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'scheme_id' => Course::factory(),
            'author_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'is_pinned' => false,
            'is_closed' => false,
            'is_resolved' => false,
            'views_count' => $this->faker->numberBetween(0, 100),
            'replies_count' => 0,
            'last_activity_at' => now(),
        ];
    }

    /**
     * Indicate that the thread is pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Indicate that the thread is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }

    /**
     * Indicate that the thread is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }
}
