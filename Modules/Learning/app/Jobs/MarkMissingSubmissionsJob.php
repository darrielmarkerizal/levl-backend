<?php

declare(strict_types=1);

namespace Modules\Learning\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class MarkMissingSubmissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        // Find assignments with deadlines passed tolerance and mark in-progress submissions as missing
        $assignments = Assignment::whereNotNull('deadline_at')
            ->where('status', '!=', 'archived')
            ->get();

        $affected = 0;

        foreach ($assignments as $assignment) {
            if (! $assignment->isPastTolerance()) {
                continue;
            }

            $submissions = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('state', SubmissionState::InProgress)
                ->get();

            foreach ($submissions as $submission) {
                // Set status to Missing and mark as submitted (without answers)
                $submission->status = SubmissionStatus::Missing;
                $submission->is_late = true;
                $submission->submitted_at = now();
                $submission->save();

                // Transition to Submitted to finalize state and trigger events
                try {
                    $submission->transitionTo(SubmissionState::Submitted, $submission->user_id);
                } catch (\Throwable $e) {
                    Log::warning('Failed to transition missing submission', [
                        'submission_id' => $submission->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $affected++;
            }
        }

        if ($affected > 0) {
            Log::info('MarkMissingSubmissionsJob: marked missing submissions', [
                'count' => $affected,
            ]);
        }
    }

    public function tags(): array
    {
        return [
            'mark-missing-submissions',
        ];
    }
}
