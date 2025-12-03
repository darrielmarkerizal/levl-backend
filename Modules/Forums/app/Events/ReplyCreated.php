<?php

namespace Modules\Forums\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Forums\Models\Reply;

class ReplyCreated
{
    use Dispatchable, SerializesModels;

    public Reply $reply;

    /**
     * Create a new event instance.
     */
    public function __construct(Reply $reply)
    {
        $this->reply = $reply;
    }
}
