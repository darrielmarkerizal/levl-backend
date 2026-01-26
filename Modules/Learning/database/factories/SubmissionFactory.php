<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Learning\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory(),
            'enrollment_id' => Enrollment::factory(),
            'answer_text' => fake()->optional(0.7)->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'submitted', 'graded', 'late']),
            'submitted_at' => fake()->boolean(70) ? $submittedAt : null,
            'attempt_number' => 1,
            'is_late' => fake()->boolean(20),
            'is_resubmission' => fake()->boolean(30),
            'previous_submission_id' => null,
            'score' => fake()->optional(0.5)->randomFloat(2, 0, 100),
            'question_set' => null,
            'state' => fake()->optional(0.4)->randomElement(['started', 'in_progress', 'submitted']),
            'started_at' => fake()->optional(0.8)->dateTimeBetween('-3 months', $submittedAt),
            'time_expired_at' => null,
            'auto_submitted_on_timeout' => false,
        ];
    }

    /**
     * Submission for assignment.
     */
    public function forAssignment(Assignment $assignment): static
    {
        return $this->state(fn (array $attributes) => [
            'assignment_id' => $assignment->id,
        ]);
    }

    /**
     * Submission for user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the submission is a draft (in progress).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'submitted_at' => null,
        ]);
    }

    /**
     * Graded submission.
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'graded',
            'score' => rand(0, 100),
        ]);
    }

    /**
     * Alias for draft - in progress state.
     */
    public function inProgress(): static
    {
        return $this->draft();
    }

    /**
     * Indicate that the submission is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that the submission is late.
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_late' => true,
        ]);
    }

    /**
     * Indicate that the submission is a resubmission.
     */
    public function resubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resubmission' => true,
            'previous_submission_id' => Submission::factory(),
            'attempt_number' => fake()->numberBetween(2, 5),
        ]);
    }

    /**
     * Set a specific score.
     */
    public function withScore(float $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'status' => 'graded',
        ]);
    }

    /**
     * Set a specific question set.
     */
    public function withQuestionSet(array $questionIds): static
    {
        return $this->state(fn (array $attributes) => [
            'question_set' => $questionIds,
        ]);
    }

    /**
     * Set a specific attempt number.
     */
    public function attempt(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'attempt_number' => $number,
            'is_resubmission' => $number > 1,
        ]);
    }
}
