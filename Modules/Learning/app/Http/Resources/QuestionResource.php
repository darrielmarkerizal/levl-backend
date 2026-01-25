<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Learning\Models\Question;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;

        $user = $request->user();
        $isInstructorOrAdmin = $user && ($user->hasRole(['Instructor', 'Admin', 'Super Admin']) || $user->can('grade', $question->assignment));

        $currentAnswer = $question->current_answer ?? null;
        $hasContent = is_string($currentAnswer?->content) && trim($currentAnswer->content) !== '';
        $hasSelectedOptions = is_array($currentAnswer?->selected_options) && count($currentAnswer->selected_options) > 0;
        $hasFiles = is_array($currentAnswer?->file_paths) && count($currentAnswer->file_paths) > 0;
        $isAnswered = $currentAnswer !== null && ($hasContent || $hasSelectedOptions || $hasFiles);

        return [
            'id' => $question->id,
            'assignment_id' => $question->assignment_id,
            'type' => $question->type?->value,
            'content' => $question->content,
            'options' => $question->options,
            'weight' => (float) $question->weight,
            'order' => $question->order,
            'max_score' => $question->max_score ? (float) $question->max_score : null,
            'max_file_size' => $question->max_file_size,
            'allowed_file_types' => $question->allowed_file_types,
            'allow_multiple_files' => $question->allow_multiple_files,
            'can_auto_grade' => $question->canAutoGrade(),
            'created_at' => $question->created_at?->toIso8601String(),
            'updated_at' => $question->updated_at?->toIso8601String(),
            'attachments' => $question->getMedia('question_attachments')->map(fn ($media) => $media->getUrl()),
            'answer_key' => $this->when($isInstructorOrAdmin, $question->answer_key),
            'is_answered' => $this->when(! $isInstructorOrAdmin, $isAnswered),
            'current_answer' => $this->when(
                isset($question->current_answer),
                fn () => $question->current_answer ? AnswerResource::make($question->current_answer) : null
            ),
        ];
    }
}
