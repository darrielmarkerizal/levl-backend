<?php

namespace Modules\Forums\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Schemes\Models\Course;

class ForumStatistic extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Forums\Database\Factories\ForumStatisticFactory::new();
    }

    protected $fillable = [
        'scheme_id',
        'user_id',
        'threads_count',
        'replies_count',
        'views_count',
        'avg_response_time_minutes',
        'response_rate',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'threads_count' => 'integer',
        'replies_count' => 'integer',
        'views_count' => 'integer',
        'avg_response_time_minutes' => 'integer',
        'response_rate' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the scheme (course) that owns the statistic.
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'scheme_id');
    }

    /**
     * Get the user that owns the statistic (null for scheme-wide stats).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    /**
     * Scope a query to only include statistics for a specific scheme.
     */
    public function scopeForScheme($query, int $schemeId)
    {
        return $query->where('scheme_id', $schemeId);
    }

    /**
     * Scope a query to only include statistics for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include scheme-wide statistics (no specific user).
     */
    public function scopeSchemeWide($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope a query to filter by period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }
}
