<?php

declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class LevelConfig extends Model
{
    use Searchable;

    protected $table = 'level_configs';

    protected $fillable = [
        'level',
        'name',
        'xp_required',
        'rewards',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp_required' => 'integer',
        'rewards' => 'array',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'name' => $this->name,
            'description' => $this->description,
            'min_points' => $this->min_points,
            'is_active' => $this->is_active,
        ];
    }

    public function searchableAs(): string
    {
        return 'level_configs_index';
    }
}
