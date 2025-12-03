<?php

namespace Modules\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;

class ProfileUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public bool $emailChanged = false
    ) {}
}
