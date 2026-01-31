<?php

declare(strict_types=1);

namespace App\Listeners;

use Laravel\Octane\Events\RequestHandled;

class MonitorWorkerMemory
{
    protected int $memoryLimitMb = 128;

    public function handle(RequestHandled $event): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;

        if ($memoryUsage > $this->memoryLimitMb) {
            gc_collect_cycles();
        }
    }
}
