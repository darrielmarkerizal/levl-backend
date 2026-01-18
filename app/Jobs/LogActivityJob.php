<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $activityData
    ) {
        $this->onQueue('logging');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        activity($this->activityData['log_name'] ?? 'default')
            ->causedBy($this->activityData['causer_id'] ?? null)
            ->performedOn($this->activityData['subject'] ?? null)
            ->withProperties($this->activityData['properties'] ?? [])
            ->log($this->activityData['description'] ?? '');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to log activity', [
            'data' => $this->activityData,
            'error' => $exception->getMessage(),
        ]);
    }
}
