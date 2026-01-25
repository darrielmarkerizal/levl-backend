<?php

namespace Modules\Questions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Common\Http\Resources\CategoryResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 🔒 SECURITY: Determine if user is authorized to see sensitive fields
        $user = $request->user();
        $isAuthorized = $user && $user->hasAnyRole(['Admin', 'Instructor', 'Superadmin']);

        return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ],
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'difficulty' => $this->difficulty?->value,
            'difficulty_label' => $this->difficulty?->label(),
            'question_text' => $this->question_text,
            'explanation' => $this->when($isAuthorized, $this->explanation),
            'points' => $this->points,
            'tags' => $this->tags,
            'meta' => $this->meta,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
            
            // ⚠️ CRITICAL: answer_key should NOT be visible to students
            'answer_key' => $this->when($isAuthorized, fn() => $this->getAnswerKey()),
            
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->when($isAuthorized, $this->updated_at?->toIso8601String()),
            'deleted_at' => $this->when($isAuthorized, $this->deleted_at?->toIso8601String()),
        ];
    }

    /**
     * Get answer key safely - only call when already authorized
     */
    private function getAnswerKey(): mixed
    {
        // Return answer_key if exists, otherwise null
        return $this->resource->answer_key ?? null;
    }
}
