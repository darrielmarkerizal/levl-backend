<?php

namespace Database\Factories;

use App\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Unit;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => null,
            'code' => CodeGenerator::generate('UNIT-', 4, Unit::class),
            'slug' => fake()->unique()->slug(),
            'title' => fake()->sentence(2),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
            'status' => 'published',
        ];
    }

    /**
     * Indicate that the unit is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
