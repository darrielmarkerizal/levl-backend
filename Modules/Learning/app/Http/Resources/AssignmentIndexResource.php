<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Assignment;

class AssignmentIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $assignment = $this->resource;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'submission_type' => $assignment->submission_type?->value ?? $assignment->submission_type,
            'max_score' => $assignment->max_score,
            'available_from' => $assignment->available_from?->toIso8601String(),
            'deadline_at' => $assignment->deadline_at?->toIso8601String(),
            'status' => $assignment->status?->value ?? $assignment->status,
            'is_available' => $assignment->isAvailable(),
            'is_past_deadline' => $assignment->isPastDeadline(),
            'created_at' => $assignment->created_at?->toIso8601String(),
            'updated_at' => $assignment->updated_at?->toIso8601String(),

            // Optimized relationships with limited fields
            'creator' => $this->whenLoaded('creator', function () use ($assignment) {
                return [
                    'id' => $assignment->creator->id,
                    'name' => $assignment->creator->name,
                ];
            }),
            
            'questions_count' => $this->when(
                $assignment->questions_count !== null,
                $assignment->questions_count
            ),
        ];
    }
}