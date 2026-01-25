<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Learning\Models\Submission;

class GradeRecalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly float $oldScore,
        public readonly float $newScore
    ) {}
}
