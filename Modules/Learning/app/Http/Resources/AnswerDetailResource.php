<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerDetailResource extends JsonResource
{
    protected array $visibility = [];

    public function withVisibility(array $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function toArray($request): array
    {
        $question = $this->whenLoaded('question');
        
        $canViewAnswers = $this->visibility['can_view_answers'] ?? false;
        $canViewScore = $this->visibility['can_view_score'] ?? false;
        $canViewFeedback = $this->visibility['can_view_feedback'] ?? false;

        $data = [
            'id' => $this->id,
            'content' => $this->content,
            'selected_options' => $this->selected_options,
            'file_paths' => $this->file_paths,
            'score' => $this->when($canViewScore, $this->score),
            'is_auto_graded' => $this->is_auto_graded,
            'feedback' => $this->when($canViewFeedback, $this->feedback),
            'question' => $this->when($question, function () use ($question, $canViewAnswers) {
                return [
                    'id' => $question->id,
                    'content' => $question->content,
                    'type' => $question->type,
                    'options' => $question->options,
                    'max_score' => $question->weight,
                    'answer_key' => $this->when($canViewAnswers, $question->answer_key),
                ];
            }),
        ];

        if ($canViewAnswers) {
            $isCorrect = null;
            if ($this->score !== null && $question && $question->weight > 0) {
                 $isCorrect = abs((float)$this->score - (float)$question->weight) < 0.001;
            } elseif ($this->score !== null && $question && $question->weight == 0) {
                 $isCorrect = false; 
            }

            $data['is_correct'] = $isCorrect;
        }

        return $data;
    }
}
