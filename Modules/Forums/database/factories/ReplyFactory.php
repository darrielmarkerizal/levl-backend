<?php

namespace Modules\Forums\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Reply;
use Modules\Forums\Models\Thread;

class ReplyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Reply::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'thread_id' => Thread::factory(),
            'parent_id' => null,
            'author_id' => User::factory(),
            'content' => $this->faker->paragraphs(2, true),
            'depth' => 0,
            'is_accepted_answer' => false,
        ];
    }

    /**
     * Indicate that the reply is an accepted answer.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_accepted_answer' => true,
        ]);
    }

    /**
     * Indicate that the reply is nested.
     */
    public function nested(Reply $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'thread_id' => $parent->thread_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
