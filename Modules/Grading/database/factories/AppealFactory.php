<?php

namespace Modules\Grading\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Learning\Models\Submission;
use Modules\Grading\Models\Appeal;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Grading\Models\Appeal>
 */
class AppealFactory extends Factory
{
    protected $model = Appeal::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'student_id' => User::factory(),
            'reviewer_id' => fake()->boolean(70) ? User::factory() : null,
            'reason' => fake()->paragraph(),
            'supporting_documents' => fake()->optional(0.5)->json(),
            'status' => fake()->randomElement(['pending', 'approved', 'denied']),
            'decision_reason' => fake()->optional(0.6)->paragraph(),
            'submitted_at' => now(),
            'decided_at' => fake()->boolean(60) ? now()->addDays(rand(1, 5)) : null,
        ];
    }

    /**
     * Appeal is pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewer_id' => null,
            'decision_reason' => null,
            'decided_at' => null,
        ]);
    }

    /**
     * Appeal is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewer_id' => User::factory(),
            'decision_reason' => fake()->paragraph(),
            'decided_at' => now(),
        ]);
    }

    /**
     * Appeal is denied.
     */
    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'denied',
            'reviewer_id' => User::factory(),
            'decision_reason' => fake()->paragraph(),
            'decided_at' => now(),
        ]);
    }

    /**
     * Appeal for a specific submission.
     */
    public function forSubmission(Submission $submission): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_id' => $submission->id,
            'student_id' => $submission->user_id,
        ]);
    }
}
