<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\UserActivity;

class UserActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\ActivityLog $activity */
        $activity = $this->resource;

        return [
            'id' => $activity->id,
            'activity_type' => $activity->event ?? $activity->description,
            'activity_data' => $activity->properties,
            'related_type' => $activity->subject_type,
            'related_id' => $activity->subject_id,
            'created_at' => $activity->created_at?->toISOString(),
            // Optional: Include new fields if useful
            'location' => [
                'city' => $activity->city,
                'region' => $activity->region,
                'country' => $activity->country,
            ],
            'device_info' => $activity->device_info,
        ];
    }
}
