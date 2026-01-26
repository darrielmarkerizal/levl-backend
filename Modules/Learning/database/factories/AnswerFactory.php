<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Submission;
use Modules\Learning\Models\Question;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Learning\Models\Answer>
 */
class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'question_id' => Question::factory(),
            'content' => fake()->optional(0.6)->paragraph(),
            'selected_options' => fake()->optional(0.5)->json(),
            'file_paths' => fake()->optional(0.3)->json(),
            'score' => fake()->optional(0.6)->randomFloat(2, 0, 100),
            'is_auto_graded' => fake()->boolean(70),
            'feedback' => fake()->optional(0.5)->paragraph(),
            'files_expired_at' => fake()->optional(0.2)->dateTime(),
            'file_metadata' => fake()->optional(0.3)->json(),
        ];
    }

    /**
     * Answer with text content.
     */
    public function textContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'content' => fake()->paragraph(),
            'selected_options' => null,
            'file_paths' => null,
        ]);
    }

    /**
     * Answer with multiple choice selection.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'selected_options' => json_encode([
                fake()->uuid(),
                fake()->uuid(),
            ]),
            'content' => null,
        ]);
    }

    /**
     * Answer with file attachments.
     */
    public function withFiles(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_paths' => json_encode([
                fake()->sha256() . '.pdf',
                fake()->sha256() . '.docx',
            ]),
            'file_metadata' => json_encode([
                'total_size' => fake()->numberBetween(100000, 5000000),
                'file_count' => 2,
            ]),
        ]);
    }

    /**
     * Answer for a specific submission.
     */
    public function forSubmission(Submission $submission): static
    {
        return $this->state(fn (array $attributes) => [
            'submission_id' => $submission->id,
        ]);
    }

    /**
     * Answer for a specific question.
     */
    public function forQuestion(Question $question): static
    {
        return $this->state(fn (array $attributes) => [
            'question_id' => $question->id,
        ]);
    }
}
