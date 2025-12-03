<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePrivacySetting extends Model
{
    const VISIBILITY_PUBLIC = 'public';

    const VISIBILITY_PRIVATE = 'private';

    const VISIBILITY_FRIENDS = 'friends_only';

    protected $fillable = [
        'user_id',
        'profile_visibility',
        'show_email',
        'show_phone',
        'show_activity_history',
        'show_achievements',
        'show_statistics',
    ];

    protected $casts = [
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'show_activity_history' => 'boolean',
        'show_achievements' => 'boolean',
        'show_statistics' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPublic(): bool
    {
        return $this->profile_visibility === self::VISIBILITY_PUBLIC;
    }

    public function canShowField(string $field, User $viewer): bool
    {
        // Admin can see everything
        if ($viewer->hasRole('Admin') || $viewer->hasRole('Superadmin')) {
            return true;
        }

        // Owner can see everything
        if ($this->user_id === $viewer->id) {
            return true;
        }

        // Check profile visibility first
        if ($this->profile_visibility === self::VISIBILITY_PRIVATE) {
            return false;
        }

        // Check field-specific settings
        $fieldMap = [
            'email' => 'show_email',
            'phone' => 'show_phone',
            'activity_history' => 'show_activity_history',
            'achievements' => 'show_achievements',
            'statistics' => 'show_statistics',
        ];

        if (isset($fieldMap[$field])) {
            return (bool) $this->{$fieldMap[$field]};
        }

        // Default to public for basic fields
        return true;
    }
}
