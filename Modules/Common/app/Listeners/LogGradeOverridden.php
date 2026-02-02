<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Grading\Events\GradeOverridden;

class LogGradeOverridden implements ShouldQueue
{
    public string $queue = 'audit';

    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    public function handle(GradeOverridden $event): void
    {
        $this->auditService->logGradeOverride(
            $event->grade,
            $event->oldScore,
            $event->newScore,
            $event->reason,
            $event->instructorId
        );
    }
}
