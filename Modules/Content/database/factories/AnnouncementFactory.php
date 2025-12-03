<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Schemes\Models\Course;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'course_id' => null,
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'status' => 'draft',
            'target_type' => 'all',
            'target_value' => null,
            'priority' => 'normal',
            'published_at' => null,
            'scheduled_at' => null,
            'views_count' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(rand(1, 7)),
        ]);
    }

    public function forCourse(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => Course::factory(),
            'target_type' => 'course',
        ]);
    }

    public function forRole(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'role',
            'target_value' => $role,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
