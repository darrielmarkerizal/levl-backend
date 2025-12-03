<?php

namespace Modules\Forums\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Forums\Models\Thread;

class ThreadCreated
{
    use Dispatchable, SerializesModels;

    public Thread $thread;

    /**
     * Create a new event instance.
     */
    public function __construct(Thread $thread)
    {
        $this->thread = $thread;
    }
}
