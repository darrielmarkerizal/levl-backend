<?php

namespace Modules\Schemes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Enums\CourseStatus;
use Modules\Schemes\Enums\CourseType;
use Modules\Schemes\Enums\EnrollmentType;
use Modules\Schemes\Enums\ProgressionMode;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Schemes\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3}\d{3}'),
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'short_desc' => fake()->paragraph(),
            'type' => fake()->randomElement([CourseType::Okupasi->value, CourseType::Kluster->value]),
            'level_tag' => fake()->randomElement(['dasar', 'menengah', 'mahir']),
            'enrollment_type' => fake()->randomElement([EnrollmentType::AutoAccept->value, EnrollmentType::KeyBased->value, EnrollmentType::Approval->value]),
            'progression_mode' => fake()->randomElement([ProgressionMode::Sequential->value, ProgressionMode::Free->value]),
            'status' => fake()->randomElement([CourseStatus::Published->value, CourseStatus::Draft->value]),
            'tags_json' => json_encode([]),
            'prereq_text' => fake()->optional(0.3)->paragraph(),
            'published_at' => fake()->boolean(70) ? now()->subDays(rand(1, 30)) : null,
        ];
    }

    /**
     * Indicate course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published->value,
            'published_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Indicate course is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate course enrollment is open.
     */
    public function openEnrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_type' => EnrollmentType::AutoAccept->value,
        ]);
    }

    /**
     * Indicate course is online type (Okupasi - online).
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Okupasi->value,
        ]);
    }

    /**
     * Indicate course is hybrid type (Kluster).
     */
    public function hybrid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CourseType::Kluster->value,
        ]);
    }
}
