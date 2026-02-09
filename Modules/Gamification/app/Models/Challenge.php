<?php

namespace Modules\Gamification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Modules\Gamification\Enums\ChallengeType;

class Challenge extends Model
{
  use Searchable;
    protected $table = 'challenges';

    protected $fillable = [
        'title',
        'description',
        'type',
        'criteria',
        'target_count',
        'points_reward',
        'badge_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'badge_id' => 'integer',
        'points_reward' => 'integer',
        'target_count' => 'integer',
        'criteria' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'type' => ChallengeType::class,
    ];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserChallengeAssignment::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(UserChallengeCompletion::class);
    }

    public function scopeDaily($query)
    {
        return $query->where('type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('type', 'weekly');
    }

    public function scopeSpecial($query)
    {
        return $query->where('type', 'special');
    }

    public function scopeActive($query, bool $isActive = true)
    {
        if ($isActive) {
            return $query->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('start_at')
                        ->orWhere('start_at', '<=', now());
                })
                ->where(function ($subQ) {
                    $subQ->whereNull('end_at')
                        ->orWhere('end_at', '>=', now());
                });
            });
        }
        return $query->where(function ($q) {
            $q->where('start_at', '>', now())
                ->orWhere('end_at', '<', now());
        });
    }

    public function isActive(): bool
    {
        $now = now();
        $started = $this->start_at === null || $this->start_at->lte($now);
        $notEnded = $this->end_at === null || $this->end_at->gte($now);

        return $started && $notEnded;
    }

    public function getCriteriaTypeAttribute(): ?string
    {
        return $this->criteria['type'] ?? null;
    }

    public function getCriteriaTargetAttribute(): int
    {
        return $this->criteria['target'] ?? $this->target_count;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'badge_id' => $this->badge_id,
            'points_reward' => $this->points_reward,
            'target_count' => $this->target_count,
            'start_at' => $this->start_at?->timestamp,
            'end_at' => $this->end_at?->timestamp,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function searchableAs(): string
    {
        return 'challenges_index';
    }
}
