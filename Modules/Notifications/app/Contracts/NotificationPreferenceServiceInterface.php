<?php

namespace Modules\Notifications\Contracts;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;

interface NotificationPreferenceServiceInterface
{
    /**
     * Get user's notification preferences.
     */
    public function getPreferences(User $user): Collection;

    /**
     * Update user's notification preferences.
     */
    public function updatePreferences(User $user, array $preferences): bool;

    /**
     * Check if notification should be sent based on user preferences.
     */
    public function shouldSendNotification(User $user, string $category, string $channel): bool;

    /**
     * Reset user preferences to defaults.
     */
    public function resetToDefaults(User $user): bool;

    /**
     * Get default preferences for a new user.
     */
    public function getDefaultPreferences(): array;
}
