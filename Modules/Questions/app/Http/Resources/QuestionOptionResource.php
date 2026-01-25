<?php

namespace Modules\Questions\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 🔒 SECURITY: Only expose is_correct to authorized users (instructors/admins)
        $user = $request->user();
        $isAuthorized = $user && $user->hasAnyRole(['Admin', 'Instructor', 'Superadmin']);

        return [
            'id' => $this->id,
            'option_key' => $this->option_key,
            'option_text' => $this->option_text,
            
            // ⚠️ CRITICAL: Only include is_correct for authorized users
            // Students should NOT see which options are correct
            'is_correct' => $this->when($isAuthorized, $this->is_correct),
            
            'order' => $this->order,
            
            // Only include updated_at for authorized users
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->when($isAuthorized, $this->updated_at?->toIso8601String()),
        ];
    }
}
