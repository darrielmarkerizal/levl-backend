<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Enums\ContentType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'markdown_content' => fake()->paragraph(5),
            'content_type' => fake()->randomElement(['markdown', 'video', 'link']),
            'content_url' => fake()->optional(0.4)->url(),
            'order' => fake()->numberBetween(1, 20),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
        ];
    }

    /**
     * Lesson with video content.
     */
    public function videoContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => ContentType::Video->value,
        ]);
    }

    /**
     * Lesson with document content.
     */
    public function documentContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => ContentType::Document->value,
        ]);
    }

    /**
     * Lesson for unit.
     */
    public function forUnit(Unit $unit): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_id' => $unit->id,
        ]);
    }
}
