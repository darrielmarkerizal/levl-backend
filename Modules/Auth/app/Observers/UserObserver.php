<?php

namespace Modules\Auth\Observers;

use Modules\Auth\Models\ProfilePrivacySetting;
use Modules\Auth\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        // Create default privacy settings for new user
        ProfilePrivacySetting::create([
            'user_id' => $user->id,
            'profile_visibility' => ProfilePrivacySetting::VISIBILITY_PUBLIC,
            'show_email' => false,
            'show_phone' => false,
            'show_activity_history' => true,
            'show_achievements' => true,
            'show_statistics' => true,
        ]);
    }
}
