<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class Leaderboard extends Model
{
    protected $table = 'leaderboards';

    protected $fillable = [
        'course_id',
        'user_id',
        'rank',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'user_id' => 'integer',
        'rank' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isGlobal(): bool
    {
        return $this->course_id === null;
    }

    public function getTotalPointsAttribute(): int
    {
        if ($this->isGlobal()) {
            return $this->user->gamificationStats?->total_xp ?? 0;
        }

        $coursePoints = \Modules\Gamification\Models\Point::where('user_id', $this->user_id)
            ->whereIn('source_type', ['lesson', 'assignment', 'attempt'])
            ->sum('points');

        return (int) $coursePoints;
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('course_id')
            ->join('user_gamification_stats', 'leaderboards.user_id', '=', 'user_gamification_stats.user_id')
            ->orderBy('user_gamification_stats.total_xp', 'desc')
            ->orderBy('leaderboards.rank', 'asc')
            ->select('leaderboards.*');
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId)
            ->orderBy('rank', 'asc');
    }

    public function scopeTop($query, $limit = 10)
    {
        return $query->orderBy('rank', 'asc')
            ->limit($limit);
    }
}

