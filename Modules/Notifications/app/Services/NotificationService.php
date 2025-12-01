<?php

namespace Modules\Notifications\Services;

use Modules\Notifications\Models\Notification;

class NotificationService
{
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
}
