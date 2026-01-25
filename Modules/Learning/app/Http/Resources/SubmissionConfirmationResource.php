<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Enums\SubmissionState;

class SubmissionConfirmationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['assignment', 'user', 'answers']);

        $totalQuestions = count($this->question_set ?? []);
        $answeredCount = $this->answers->count();

        $isPendingGrade = in_array($this->state, [
            SubmissionState::Submitted,
            SubmissionState::PendingManualGrading,
        ]);

        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'assignment_title' => $this->assignment->title,
            'status' => $this->status,
            'attempt_number' => $this->attempt_number,
            'is_late' => $this->is_late,
            'submitted_at' => $this->submitted_at, 
            'duration' => $this->duration,
            'duration_formatted' => $this->formatted_duration,
            'summary' => [
                'total_questions' => $totalQuestions,
                'answered' => $answeredCount,
                'pending_grade' => $isPendingGrade,
            ],
            'student' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
            ],
        ];
    }
}
