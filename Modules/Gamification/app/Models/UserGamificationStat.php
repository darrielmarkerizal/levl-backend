<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class UserGamificationStat extends Model
{
    protected $table = 'user_gamification_stats';

    protected $fillable = [
        'user_id',
        'total_xp',
        'global_level',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'stats_updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_xp' => 'integer',
        'global_level' => 'integer',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'date',
        'stats_updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getXpToNextLevelAttribute(): int
    {
        return $this->calculateXpRequiredForLevel($this->global_level);
    }

    public function getProgressToNextLevelAttribute(): float
    {
        $xpToNext = $this->xp_to_next_level;
        if ($xpToNext <= 0) {
            return 100.0;
        }

        $currentLevelXp = $this->calculateCurrentLevelXp();
        return min(100.0, ($currentLevelXp / $xpToNext) * 100);
    }

    public function getCurrentLevelXpAttribute(): int
    {
        return $this->calculateCurrentLevelXp();
    }

    private function calculateCurrentLevelXp(): int
    {
        $previousLevelXp = $this->calculateXpForLevel($this->global_level - 1);
        return max(0, $this->total_xp - $previousLevelXp);
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

