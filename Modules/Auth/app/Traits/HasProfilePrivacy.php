<?php

namespace Modules\Auth\Traits;

use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

trait HasProfilePrivacy
{
    public function canBeViewedBy(User $viewer): bool
    {
        // Admin can view all profiles
        if ($viewer->hasRole('Admin') || $viewer->hasRole('Superadmin')) {
            return true;
        }

        // User can view their own profile
        if ($this->id === $viewer->id) {
            return true;
        }

        $privacySettings = $this->privacySettings;

        if (! $privacySettings) {
            return true; // Default to public if no settings
        }

        // Check profile visibility
        if ($privacySettings->profile_visibility === ProfilePrivacySetting::VISIBILITY_PRIVATE) {
            return false;
        }

        // TODO: Implement friends_only check when social features are added
        if ($privacySettings->profile_visibility === ProfilePrivacySetting::VISIBILITY_FRIENDS) {
            return false; // For now, treat as private
        }

        return true;
    }

    public function getVisibleFieldsFor(User $viewer): array
    {
        // Admin can see all fields
        if ($viewer->hasRole('Admin') || $viewer->hasRole('Superadmin')) {
            return ['*'];
        }

        // User can see all their own fields
        if ($this->id === $viewer->id) {
            return ['*'];
        }

        $privacySettings = $this->privacySettings;

        if (! $privacySettings) {
            return ['name', 'avatar_path', 'bio']; // Default visible fields
        }

        $visibleFields = ['name', 'avatar_path', 'bio'];

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
