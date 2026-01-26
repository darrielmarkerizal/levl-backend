<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Models\Course;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'code' => fake()->regexify('[A-Z]{3}\d{2}'),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Unit belongs to course.
     */
    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
        ]);
    }
}
