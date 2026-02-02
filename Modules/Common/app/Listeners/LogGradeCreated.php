<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Grading\Events\GradeCreated;

class LogGradeCreated implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'audit';

    public function __construct(
        private readonly AuditServiceInterface $auditService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(GradeCreated $event): void
    {
        $this->auditService->logGrading($event->grade, $event->instructorId);
    }
}
