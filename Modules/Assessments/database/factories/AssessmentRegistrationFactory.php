<?php

namespace Modules\Assessments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;

class AssessmentRegistrationFactory extends Factory
{
    protected $model = AssessmentRegistration::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exercise_id' => Exercise::factory(),
            'enrollment_id' => Enrollment::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'status' => AssessmentRegistration::STATUS_PENDING,
            'payment_status' => AssessmentRegistration::PAYMENT_PENDING,
            'payment_amount' => $this->faker->randomFloat(2, 50, 500),
            'payment_method' => null,
            'payment_reference' => null,
            'prerequisites_met' => false,
            'prerequisites_checked_at' => null,
            'confirmation_sent_at' => null,
            'reminder_sent_at' => null,
            'completed_at' => null,
            'result' => null,
            'score' => null,
            'notes' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssessmentRegistration::STATUS_CONFIRMED,
            'prerequisites_met' => true,
            'prerequisites_checked_at' => now(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssessmentRegistration::STATUS_SCHEDULED,
            'prerequisites_met' => true,
            'prerequisites_checked_at' => now(),
            'confirmation_sent_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssessmentRegistration::STATUS_COMPLETED,
            'prerequisites_met' => true,
            'prerequisites_checked_at' => now(),
            'confirmation_sent_at' => now(),
            'completed_at' => now(),
            'result' => 'passed',
            'score' => $this->faker->randomFloat(2, 70, 100),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => AssessmentRegistration::PAYMENT_PAID,
            'payment_method' => $this->faker->randomElement(['credit_card', 'bank_transfer', 'e_wallet']),
            'payment_reference' => $this->faker->uuid(),
        ]);
    }
}
