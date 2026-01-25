<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Appeal;

class AppealSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appeal $appeal,
        public readonly int $instructorId
    ) {}
}
