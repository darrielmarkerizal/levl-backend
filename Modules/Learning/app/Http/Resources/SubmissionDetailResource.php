<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionDetailResource extends JsonResource
{
    protected array $visibility = [];

    public function withVisibility(array $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function toArray(Request $request): array
    {
        /** @var \Modules\Learning\Models\Submission $this */
        $this->loadMissing(['assignment', 'answers']);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'attempt_number' => $this->attempt_number,
            'score' => $this->score,
            'is_late' => $this->is_late,
            'submitted_at' => $this->submitted_at,
            'duration' => $this->duration,
            'duration_formatted' => $this->formatted_duration,
            'graded_at' => $this->graded_at,
            'assignment' => [
                'id' => $this->assignment->id,
                'title' => $this->assignment->title,
            ],
            'answers' => $this->answers->map(function ($answer) {
                 return (new AnswerDetailResource($answer))->withVisibility($this->visibility);
            }),
        ];
    }
}
