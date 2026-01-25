<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Grade;

class GradeOverridden
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Grade $grade,
        public readonly float $oldScore,
        public readonly float $newScore,
        public readonly string $reason,
        public readonly int $instructorId
    ) {}
}
