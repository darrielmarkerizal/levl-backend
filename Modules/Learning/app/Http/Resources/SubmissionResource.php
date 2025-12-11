<?php

namespace Modules\Learning\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
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
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'enrollment_id' => $this->enrollment_id,
            'answer_text' => $this->answer_text,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'graded_at' => $this->graded_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'assignment' => $this->whenLoaded('assignment'),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'enrollment' => $this->whenLoaded('enrollment'),
            'files' => $this->whenLoaded('files'),
            'previousSubmission' => $this->whenLoaded('previousSubmission'),
            'grade' => $this->whenLoaded('grade'),
        ];
    }

    /**
     * Create a new resource instance with default relationships loaded.
     */
    public static function make($resource)
    {
        if ($resource && method_exists($resource, 'loadMissing')) {
            $resource->loadMissing([
                'assignment',
                'user:id,name,email',
                'enrollment',
                'files',
                'previousSubmission',
                'grade',
            ]);
        }

        return parent::make($resource);
    }
}
