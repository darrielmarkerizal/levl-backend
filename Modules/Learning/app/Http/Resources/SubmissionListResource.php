<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Modules\Learning\Models\Submission $this */
        return [
            'id' => $this->id,
            'status' => $this->status,
            'attempt_number' => $this->attempt_number,
            'score' => $this->score,
            'submitted_at' => $this->submitted_at,
            'graded_at' => $this->graded_at,
            'is_late' => $this->is_late,
            'is_highest' => $this->when(isset($this->is_highest), $this->is_highest),
            'summary' => [
                'questions_count' => count($this->question_set ?? []),
                'answered_count' => $this->answers_count ?? $this->answers->count(),
            ],
        ];
    }
}
