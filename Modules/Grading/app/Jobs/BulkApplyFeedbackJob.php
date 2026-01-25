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

class BulkApplyFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public array $submissionIds,
        public string $feedback,
        public ?int $instructorId = null
    ) {
        $this->onQueue('grading');
    }

    public function handle(GradingServiceInterface $gradingService): void
    {
        if (empty($this->submissionIds)) {
            Log::info('BulkApplyFeedbackJob: No submission IDs provided, skipping');
            return;
        }

        if (empty(trim($this->feedback))) {
            Log::warning('BulkApplyFeedbackJob: Empty feedback provided, skipping');
            return;
        }

        Log::info('BulkApplyFeedbackJob: Starting bulk feedback application', [
            'submission_count' => count($this->submissionIds),
            'feedback_length' => strlen($this->feedback),
            'instructor_id' => $this->instructorId,
        ]);

        try {
            $result = $gradingService->bulkApplyFeedback($this->submissionIds, $this->feedback);

            Log::info('BulkApplyFeedbackJob: Completed bulk feedback application', [
                'success_count' => $result['success'],
                'failed_count' => $result['failed'],
                'errors' => $result['errors'],
                'instructor_id' => $this->instructorId,
            ]);
        } catch (\Throwable $e) {
            Log::error('BulkApplyFeedbackJob: Failed to apply feedback', [
                'submission_ids' => $this->submissionIds,
                'instructor_id' => $this->instructorId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BulkApplyFeedbackJob: Job failed after all retries', [
            'submission_ids' => $this->submissionIds,
            'instructor_id' => $this->instructorId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function tags(): array
    {
        return [
            'bulk-apply-feedback',
            'instructor:'.($this->instructorId ?? 'unknown'),
            'submissions:'.count($this->submissionIds),
        ];
    }
}
