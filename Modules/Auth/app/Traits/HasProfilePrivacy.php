<?php

declare(strict_types=1);

namespace Modules\Auth\Traits;

use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

trait HasProfilePrivacy
{
    public function canBeViewedBy(User $viewer): bool
    {
        if ($viewer->hasRole('Admin') || $viewer->hasRole('Superadmin')) {
            return true;
        }

        if ($this->id === $viewer->id) {
            return true;
        }

        $privacySettings = $this->relationLoaded('privacySettings')
            ? $this->privacySettings
            : $this->privacySettings()->first();

        if (! $privacySettings) {
            return true;
        }

        if ($privacySettings->profile_visibility === ProfilePrivacySetting::VISIBILITY_PRIVATE) {
            return false;
        }

        if ($privacySettings->profile_visibility === ProfilePrivacySetting::VISIBILITY_FRIENDS) {
            return false;
        }

        return true;
    }

    public function getVisibleFieldsFor(User $viewer): array
    {
        if ($viewer->hasRole('Admin') || $viewer->hasRole('Superadmin')) {
            return ['*'];
        }

        if ($this->id === $viewer->id) {
            return ['*'];
        }

        $privacySettings = $this->relationLoaded('privacySettings')
            ? $this->privacySettings
            : $this->privacySettings()->first();

        if (! $privacySettings) {
            return ['name', 'avatar_url', 'bio'];
        }

        $visibleFields = ['name', 'avatar_url', 'bio'];

        if ($privacySettings->show_email) {
            $visibleFields[] = 'email';
        }

        if ($privacySettings->show_phone) {
            $visibleFields[] = 'phone';
        }

        if ($privacySettings->show_activity_history) {
            $visibleFields[] = 'activity_history';
        }

        if ($privacySettings->show_achievements) {
            $visibleFields[] = 'achievements';
        }

        if ($privacySettings->show_statistics) {
            $visibleFields[] = 'statistics';
        }

        return $visibleFields;
    }
}
