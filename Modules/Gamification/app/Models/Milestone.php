<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Milestone extends Model
{
    use HasFactory;

    protected $table = 'gamification_milestones';

    protected $fillable = [
        'code',
        'name',
        'description',
        'xp_required',
        'level_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'xp_required' => 'integer',
        'level_required' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active milestones
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered milestones
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
