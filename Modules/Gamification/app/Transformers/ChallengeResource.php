<?php

namespace Modules\Gamification\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'points_reward' => $this->points_reward,
            'criteria_type' => $this->criteria_type,
            'criteria_target' => $this->criteria_target,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'badge' => $this->whenLoaded('badge', function () {
                return [
                    'id' => $this->badge->id,
                    'name' => $this->badge->name,
                    'icon_url' => $this->badge->icon_url,
                ];
            }),
            'user_progress' => $this->when($this->user_progress, $this->user_progress),
        ];
    }
}
