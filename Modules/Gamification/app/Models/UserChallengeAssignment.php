<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class UserChallengeAssignment extends Model
{
    protected $table = 'user_challenge_assignments';

    protected $fillable = [
        'user_id',
        'challenge_id',
        'assigned_date',
        'status',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'challenge_id' => 'integer',
        'assigned_date' => 'date',
        'status' => 'string',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('assigned_date', $date);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

