<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Notifications\Contracts\Services\NotificationPreferenceServiceInterface;
use Modules\Notifications\DTOs\UpdateNotificationPreferencesDTO;
use Modules\Notifications\Models\NotificationPreference;

class NotificationPreferenceService implements NotificationPreferenceServiceInterface
{
    /**
     * Get user's notification preferences.
     */
    public function getPreferences(User $user): Collection
    {
        $preferences = NotificationPreference::where('user_id', $user->id)->get();

        // If user has no preferences, create defaults
        if ($preferences->isEmpty()) {
            $this->createDefaultPreferences($user);
            $preferences = NotificationPreference::where('user_id', $user->id)->get();
        }

        return $preferences;
    }

    /**
     * Update user's notification preferences from DTO or array.
     */
    public function updatePreferences(User $user, UpdateNotificationPreferencesDTO|array $preferences): bool
    {
        // Convert array to DTO if needed
        if (is_array($preferences)) {
            $preferences = UpdateNotificationPreferencesDTO::from(['preferences' => $preferences]);
        }

        return DB::transaction(function () use ($user, $preferences) {
            foreach ($preferences->preferences as $preference) {
                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category' => $preference->category,
                        'channel' => $preference->channel,
                    ],
                    [
                        'enabled' => $preference->enabled,
                        'frequency' => $preference->frequency,
                    ]
                );
            }

            return true;
        });
    }

    /**
     * Check if notification should be sent based on user preferences.
     */
    public function shouldSendNotification(User $user, string $category, string $channel): bool
    {
        // Critical system notifications should always be sent
        if ($this->isCriticalNotification($category)) {
            return true;
        }

        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        // If no preference exists, check defaults
        if (! $preference) {
            return $this->shouldSendByDefault($category, $channel);
        }

        return $preference->enabled;
    }

    /**
     * Reset user preferences to defaults.
     */
    public function resetToDefaults(User $user): bool
    {
        try {
            DB::beginTransaction();

            // Delete existing preferences
            NotificationPreference::where('user_id', $user->id)->delete();

            // Create default preferences
            $this->createDefaultPreferences($user);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Get default preferences for a new user.
     */
    public function getDefaultPreferences(): array
    {
        $categories = NotificationPreference::getCategories();
        $channels = NotificationPreference::getChannels();

        return collect($categories)
            ->crossJoin($channels)
            ->map(fn ($item) => [
                'category' => $item[0],
                'channel' => $item[1],
                'enabled' => $this->getDefaultEnabledState($item[0], $item[1]),
                'frequency' => $this->getDefaultFrequency($item[0], $item[1]),
            ])
            ->all();
    }

    /**
     * Create default preferences for a user.
     */
    protected function createDefaultPreferences(User $user): void
    {
        $defaults = $this->getDefaultPreferences();

        foreach ($defaults as $default) {
            NotificationPreference::create([
                'user_id' => $user->id,
                'category' => $default['category'],
                'channel' => $default['channel'],
                'enabled' => $default['enabled'],
                'frequency' => $default['frequency'],
            ]);
        }
    }

    /**
     * Check if a notification is critical.
     */
    protected function isCriticalNotification(string $category): bool
    {
        $criticalCategories = [
            NotificationPreference::CATEGORY_SYSTEM,
        ];

        return collect($criticalCategories)->contains($category);
    }

    /**
     * Get default enabled state for a category and channel.
     */
    protected function getDefaultEnabledState(string $category, string $channel): bool
    {
        // Email enabled by default for important categories
        if ($channel === NotificationPreference::CHANNEL_EMAIL) {
            return collect([
                NotificationPreference::CATEGORY_ASSIGNMENTS,
                NotificationPreference::CATEGORY_SYSTEM,
            ])->contains($category);
        }

        // In-app notifications enabled for all categories
        if ($channel === NotificationPreference::CHANNEL_IN_APP) {
            return true;
        }

        // Push notifications disabled by default
        if ($channel === NotificationPreference::CHANNEL_PUSH) {
            return false;
        }

        return true;
    }

    /**
     * Get default frequency for a category and channel.
     */
    protected function getDefaultFrequency(string $category, string $channel): string
    {
        // Critical notifications should be immediate
        if ($this->isCriticalNotification($category)) {
            return NotificationPreference::FREQUENCY_IMMEDIATE;
        }

        // Forum notifications can be daily digest
        if ($category === NotificationPreference::CATEGORY_FORUM) {
            return NotificationPreference::FREQUENCY_DAILY;
        }

        return NotificationPreference::FREQUENCY_IMMEDIATE;
    }

    /**
     * Check if notification should be sent by default.
     */
    protected function shouldSendByDefault(string $category, string $channel): bool
    {
        return $this->getDefaultEnabledState($category, $channel);
    }
}
