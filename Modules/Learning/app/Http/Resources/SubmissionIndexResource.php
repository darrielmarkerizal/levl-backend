<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'state' => $this->state?->value,
            'score' => $this->score,
            'attempt_number' => $this->attempt_number,
            'is_late' => $this->is_late,
            'submitted_at' => $this->submitted_at,
            'graded_at' => $this->graded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Optimized relationships with limited fields
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'grade' => $this->whenLoaded('grade', function () {
                return [
                    'id' => $this->grade->id,
                    'score' => $this->grade->score,
                    'status' => $this->grade->status,
                    'released_at' => $this->grade->released_at,
                ];
            }),
        ];
    }
}