<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentionUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatarUrl = '';

        if (is_object($this->resource) && method_exists($this->resource, 'getFirstMedia')) {
            $avatarUrl = $this->resource->getFirstMedia('avatar')?->getUrl() ?? '';
        }

        return [
            'id' => $this['id'] ?? (is_object($this->resource) ? $this->id : null),
            'name' => $this['name'] ?? (is_object($this->resource) ? $this->name : null),
            'username' => $this['username'] ?? (is_object($this->resource) ? $this->username : null),
            'avatar_url' => $avatarUrl,
        ];
    }
}
