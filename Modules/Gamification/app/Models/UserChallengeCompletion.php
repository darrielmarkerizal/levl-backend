<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class UserChallengeCompletion extends Model
{
    protected $table = 'user_challenge_completions';

    protected $fillable = [
        'user_id',
        'challenge_id',
        'completed_date',
        'xp_earned',
        'completion_data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'challenge_id' => 'integer',
        'completed_date' => 'date',
        'xp_earned' => 'integer',
        'completion_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('completed_date', [$startDate, $endDate]);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

