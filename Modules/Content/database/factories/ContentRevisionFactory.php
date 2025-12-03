<?php

namespace Modules\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Models\User;
use Modules\Content\Models\ContentRevision;

class ContentRevisionFactory extends Factory
{
    protected $model = ContentRevision::class;

    public function definition(): array
    {
        return [
            'content_type' => 'Modules\Content\Models\Announcement',
            'content_id' => 1,
            'editor_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'revision_note' => fake()->sentence(),
        ];
    }
}
