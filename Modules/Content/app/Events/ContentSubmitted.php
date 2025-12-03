<?php

namespace Modules\Content\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;

class ContentSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $content;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $content, User $user)
    {
        $this->content = $content;
        $this->user = $user;
    }
}
