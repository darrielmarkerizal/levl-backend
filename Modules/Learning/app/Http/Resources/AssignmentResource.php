<?php

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'submission_type' => $this->submission_type,
            'max_score' => $this->max_score,
            'available_from' => $this->available_from,
            'deadline_at' => $this->deadline_at,
            'status' => $this->status,
            'allow_resubmit' => $this->allow_resubmit,
            'late_penalty_percent' => $this->late_penalty_percent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'lesson' => $this->whenLoaded('lesson', function () {
                return [
                    'id' => $this->lesson->id,
                    'title' => $this->lesson->title,
                    'slug' => $this->lesson->slug,
                ];
            }),
        ];
    }

    /**
     * Create a new resource instance with default relationships loaded.
     */
    public static function make($resource)
    {
        if ($resource && method_exists($resource, 'loadMissing')) {
            $resource->loadMissing(['creator:id,name,email', 'lesson:id,title,slug']);
        }

        return parent::make($resource);
    }
}
