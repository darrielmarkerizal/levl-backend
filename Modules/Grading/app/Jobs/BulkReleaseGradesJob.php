<?php

declare(strict_types=1);

namespace Modules\Grading\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Grading\Contracts\Services\GradingServiceInterface;

class BulkReleaseGradesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public array $submissionIds,
        public ?int $instructorId = null
    ) {
        $this->onQueue('grading');
    }

    public function handle(GradingServiceInterface $gradingService): void
    {
        if (empty($this->submissionIds)) {
            Log::info('BulkReleaseGradesJob: No submission IDs provided, skipping');
            return;
        }

        Log::info('BulkReleaseGradesJob: Starting bulk grade release', [
            'submission_count' => count($this->submissionIds),
            'instructor_id' => $this->instructorId,
        ]);

        try {
            $result = $gradingService->bulkReleaseGrades($this->submissionIds);

            Log::info('BulkReleaseGradesJob: Completed bulk grade release', [
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'errors' => $result['errors'],
                'instructor_id' => $this->instructorId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BulkReleaseGradesJob: Failed to release grades', [
                'submission_ids' => $this->submissionIds,
                'instructor_id' => $this->instructorId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BulkReleaseGradesJob: Job failed after all retries', [
            'submission_ids' => $this->submissionIds,
            'instructor_id' => $this->instructorId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function tags(): array
    {
        return [
            'bulk-release-grades',
            'instructor:'.($this->instructorId ?? 'unknown'),
            'submissions:'.count($this->submissionIds),
        ];
    }
}
