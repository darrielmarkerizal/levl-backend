<?php

namespace Modules\Content\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $content;

    public function __construct($content)
    {
        $this->content = $content;
    }
}
