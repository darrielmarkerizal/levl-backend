<?php

namespace Modules\Content\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Content\Models\News;

class NewsPublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public News $news;

    public function __construct(News $news)
    {
        $this->news = $news;
    }
}
