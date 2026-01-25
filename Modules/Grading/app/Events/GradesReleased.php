<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GradesReleased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Collection $submissions,
        public readonly ?int $instructorId = null
    ) {}
}
