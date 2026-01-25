<?php

declare(strict_types=1);

namespace Modules\Grading\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Grading\Models\Appeal;

class AppealDecided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appeal $appeal
    ) {}

    public function isApproved(): bool
    {
        return $this->appeal->isApproved();
    }

    public function isDenied(): bool
    {
        return $this->appeal->isDenied();
    }

    public function getDecisionReason(): ?string
    {
        return $this->appeal->decision_reason;
    }
}
