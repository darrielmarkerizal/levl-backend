<?php

declare(strict_types=1);

namespace Modules\Common\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Learning\Events\AnswerKeyChanged;

class LogAnswerKeyChanged implements ShouldQueue
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
    public function handle(AnswerKeyChanged $event): void
    {
        $this->auditService->logAnswerKeyChange(
            $event->question,
            $event->oldAnswerKey,
            $event->newAnswerKey,
            $event->instructorId
        );
    }
}
