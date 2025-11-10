<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class UserBadge extends Model
{
    protected $table = 'user_badges';

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'badge_id' => 'integer',
        'earned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

