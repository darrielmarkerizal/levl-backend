<?php

namespace Modules\Notifications\Services;

use Modules\Auth\Models\User;
use Modules\Notifications\Contracts\NotificationPreferenceServiceInterface;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\Models\NotificationPreference;

class NotificationService
{
    protected NotificationPreferenceServiceInterface $preferenceService;

    public function __construct(NotificationPreferenceServiceInterface $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    public function create(array $data): Notification
    {
        $userId = $data['user_id'] ?? null;
        unset($data['user_id']);

        $notification = Notification::create($data);

        if ($userId) {
            $notification->users()->attach($userId);
        }

        return $notification;
    }

    public function markAsRead(Notification $notification): bool
    {
        return $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function send(int $userId, string $type, string $title, string $message, ?array $data = null): Notification
    {
        return $this->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send notification respecting user preferences.
     */
    public function sendWithPreferences(
        User $user,
        string $category,
        string $channel,
        string $title,
        string $message,
        ?array $data = null,
        bool $isCritical = false
    ): ?Notification {
        // Always send critical notifications
        if ($isCritical) {
            return $this->sendToChannel($user, $channel, $category, $title, $message, $data);
        }

        // Check if user wants to receive this notification
        if (! $this->preferenceService->shouldSendNotification($user, $category, $channel)) {
            return null;
        }

        return $this->sendToChannel($user, $channel, $category, $title, $message, $data);
    }

    /**
     * Send notification to specific channel.
     */
    protected function sendToChannel(
        User $user,
        string $channel,
        string $category,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        $notificationData = [
            'user_id' => $user->id,
            'type' => $category,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel,
        ];

        // Handle different channels
        switch ($channel) {
            case NotificationPreference::CHANNEL_EMAIL:
                $this->sendEmailNotification($user, $title, $message, $data);
                break;
            case NotificationPreference::CHANNEL_PUSH:
                $this->sendPushNotification($user, $title, $message, $data);
                break;
            case NotificationPreference::CHANNEL_IN_APP:
            default:
                // In-app notifications are always created
                break;
        }

        return $this->create($notificationData);
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(User $user, string $title, string $message, ?array $data = null): void
    {
        // TODO: Implement email sending logic
        // This would typically use Laravel's Mail facade
    }

    /**
     * Send push notification.
     */
    protected function sendPushNotification(User $user, string $title, string $message, ?array $data = null): void
    {
        // TODO: Implement push notification logic
        // This would typically use a service like Firebase Cloud Messaging
    }
}
