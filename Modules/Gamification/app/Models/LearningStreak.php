<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class LearningStreak extends Model
{
    protected $table = 'learning_streaks';

    protected $fillable = [
        'user_id',
        'activity_date',
        'xp_earned',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'activity_date' => 'date',
        'xp_earned' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

