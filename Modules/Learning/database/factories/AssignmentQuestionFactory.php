<?php

namespace Modules\Learning\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Assignment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Learning\Models\Question>
 */
class AssignmentQuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'type' => fake()->randomElement(['essay', 'multiple_choice', 'short_answer', 'file_upload']),
            'content' => fake()->sentence(15),
            'options' => null,
            'answer_key' => fake()->optional(0.7)->json(),
            'weight' => fake()->randomFloat(2, 1, 5),
            'order' => fake()->numberBetween(1, 20),
            'max_score' => fake()->randomElement([10, 20, 25, 50, 100]),
            'max_file_size' => fake()->optional(0.5)->numberBetween(1000000, 50000000),
            'allowed_file_types' => fake()->optional(0.4)->json(),
            'allow_multiple_files' => fake()->boolean(50),
        ];
    }

    /**
     * Multiple choice question with options.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'multiple_choice',
            'options' => json_encode([
                [
                    'id' => fake()->uuid(),
                    'label' => fake()->sentence(3),
                ],
                [
                    'id' => fake()->uuid(),
                    'label' => fake()->sentence(3),
                ],
                [
                    'id' => fake()->uuid(),
                    'label' => fake()->sentence(3),
                ],
                [
                    'id' => fake()->uuid(),
                    'label' => fake()->sentence(3),
                ],
            ]),
            'answer_key' => json_encode(['correct_option' => 0]),
        ]);
    }

    /**
     * Essay question.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'essay',
            'content' => fake()->paragraph(3),
            'options' => null,
            'answer_key' => null,
            'max_score' => 50,
        ]);
    }

    /**
     * Short answer question.
     */
    public function shortAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'short_answer',
            'content' => fake()->sentence(10),
            'options' => null,
            'answer_key' => json_encode(['acceptable_answers' => [
                fake()->word(),
                fake()->word(),
                fake()->word(),
            ]]),
            'max_score' => 20,
        ]);
    }

    /**
     * File upload question.
     */
    public function fileUpload(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'file_upload',
            'content' => fake()->sentence(8),
            'options' => null,
            'answer_key' => null,
            'max_file_size' => 10000000,
            'allowed_file_types' => json_encode(['pdf', 'docx', 'txt', 'png', 'jpg']),
            'allow_multiple_files' => true,
        ]);
    }

    /**
     * Question for a specific assignment.
     */
    public function forAssignment(Assignment $assignment): static
    {
        return $this->state(fn (array $attributes) => [
            'assignment_id' => $assignment->id,
        ]);
    }
}
