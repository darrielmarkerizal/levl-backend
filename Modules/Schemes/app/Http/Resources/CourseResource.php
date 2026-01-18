<?php

namespace Modules\Schemes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_desc' => $this->short_desc,
            'type' => $this->type,
            'level_tag' => $this->level_tag,
            'enrollment_type' => $this->enrollment_type,
            'progression_mode' => $this->progression_mode,
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'thumbnail' => $this->getFirstMediaUrl('thumbnail'),
            'banner' => $this->getFirstMediaUrl('banner'),
            'category' => $this->whenLoaded('category'),
            'instructor' => new \Modules\Auth\Http\Resources\UserResource($this->whenLoaded('instructor')),
            'creator' => new \Modules\Auth\Http\Resources\UserResource($this->creator),
            'admins' => \Modules\Auth\Http\Resources\UserResource::collection($this->whenLoaded('admins')),
            'tags' => $this->whenLoaded('tags'),
            'units' => $this->whenLoaded('units'),
        ];
    }
}
