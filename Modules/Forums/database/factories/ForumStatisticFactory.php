<?php

namespace Modules\Forums\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Forums\Models\ForumStatistic;
use Modules\Schemes\Models\Course;

class ForumStatisticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ForumStatistic::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Use unique month/year combination to avoid unique constraint violations
        $month = $this->faker->unique()->numberBetween(1, 12);
        $year = $this->faker->numberBetween(2020, 2025);
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        return [
            'scheme_id' => Course::factory(),
            'user_id' => null,
            'threads_count' => $this->faker->numberBetween(0, 100),
            'replies_count' => $this->faker->numberBetween(0, 500),
            'views_count' => $this->faker->numberBetween(0, 1000),
            'avg_response_time_minutes' => $this->faker->numberBetween(10, 120),
            'response_rate' => $this->faker->randomFloat(2, 0, 100),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];
    }

    /**
     * Indicate that the statistic is for a specific user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
