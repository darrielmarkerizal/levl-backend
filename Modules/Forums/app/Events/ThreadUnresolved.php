<?php

declare(strict_types=1);

namespace Modules\Forums\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Forums\Models\Thread;

class ThreadUnresolved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Thread $thread,
        public readonly User $actor
    ) {}
}
