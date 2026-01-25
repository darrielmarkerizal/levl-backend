<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'content' => $this->content,
            'selected_options' => $this->selected_options,
            'file_paths' => $this->file_paths,
            'score' => $this->score,
            'is_auto_graded' => $this->is_auto_graded,
            'feedback' => $this->feedback,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
