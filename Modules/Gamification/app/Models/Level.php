<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Schemes\Models\Course;

class Level extends Model
{
    protected $table = 'levels';

    protected $fillable = [
        'user_id',
        'course_id',
        'current_level',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'course_id' => 'integer',
        'current_level' => 'integer',
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

    public function scopeGlobal($query)
    {
        return $query->whereNull('course_id');
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getCurrentPointsAttribute(): int
    {
        if ($this->isGlobal()) {
            return $this->user->gamificationStats?->total_xp ?? 0;
        }

        $coursePoints = \Modules\Gamification\Models\Point::where('user_id', $this->user_id)
            ->whereIn('source_type', ['lesson', 'assignment', 'attempt'])
            ->sum('points');

        return (int) $coursePoints;
    }

    public function getPointsToNextAttribute(): int
    {
        return $this->calculateXpRequiredForLevel($this->current_level);
    }

    public function getProgressToNextLevelAttribute(): float
    {
        $pointsToNext = $this->points_to_next;
        if ($pointsToNext <= 0) {
            return 100.0;
        }

        $currentPoints = $this->current_points;
        $previousLevelXp = $this->calculateXpForLevel($this->current_level - 1);
        $currentLevelXp = max(0, $currentPoints - $previousLevelXp);

        return min(100.0, ($currentLevelXp / $pointsToNext) * 100);
    }

    private function calculateXpForLevel(int $level): int
    {
        if ($level <= 0) {
            return 0;
        }

        $xp = 0;
        for ($i = 1; $i < $level; $i++) {
            $xp += $this->calculateXpRequiredForLevel($i);
        }
        return $xp;
    }

    private function calculateXpRequiredForLevel(int $level): int
    {
        if ($level <= 0) {
            return 0;
        }

        return (int) (100 * pow(1.1, $level - 1));
    }
}

