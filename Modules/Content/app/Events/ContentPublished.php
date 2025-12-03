<?php

namespace Modules\Content\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $content;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $content)
    {
        $this->content = $content;
    }
}
