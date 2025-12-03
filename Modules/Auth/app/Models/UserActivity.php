<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivity extends Model
{
    const TYPE_ENROLLMENT = 'enrollment';

    const TYPE_COMPLETION = 'completion';

    const TYPE_SUBMISSION = 'submission';

    const TYPE_ACHIEVEMENT = 'achievement';

    const TYPE_BADGE_EARNED = 'badge_earned';

    const TYPE_CERTIFICATE_EARNED = 'certificate_earned';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_data',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'activity_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
