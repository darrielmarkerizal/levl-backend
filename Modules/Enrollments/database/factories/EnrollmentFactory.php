<?php

namespace Modules\Enrollments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Enrollments\Models\Enrollment;
use Modules\Enrollments\Enums\EnrollmentStatus;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Enrollments\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'status' => fake()->randomElement(['pending', 'active', 'completed', 'cancelled']),
            'enrolled_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'completed_at' => fake()->boolean(30) ? now()->subDays(rand(1, 30)) : null,
        ];
    }

    /**
     * Enrollment for user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Enrollment for course.
     */
    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
        ]);
    }

    /**
     * Active enrollment.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Active->value,
            'approved_at' => now()->subDays(rand(5, 30)),
        ]);
    }

    /**
     * Pending enrollment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Pending->value,
            'approved_at' => null,
        ]);
    }

    /**
     * Completed enrollment.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Completed->value,
            'progress_percent' => 100,
            'completed_at' => now()->subDays(rand(1, 30)),
            'approved_at' => now()->subDays(rand(30, 60)),
        ]);
    }
}
