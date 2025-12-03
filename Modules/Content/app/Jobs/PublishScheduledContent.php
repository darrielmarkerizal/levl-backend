<?php

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Content\Services\ContentSchedulingService;

class PublishScheduledContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(ContentSchedulingService $schedulingService): void
    {
        Log::info('PublishScheduledContent job started');

        $publishedCount = $schedulingService->publishScheduledContent();

        Log::info("PublishScheduledContent job completed. Published {$publishedCount} items.");
    }
}
