<?php

declare(strict_types=1);

namespace Modules\Learning\Console\Commands;

use Illuminate\Console\Command;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Enums\AssignmentStatus;
use Illuminate\Support\Facades\DB;

class PublishScheduledAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:publish-scheduled
                            {--test : Run in test mode (show what would be published without saving)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish assignments that have reached their available_from date/time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $testMode = $this->option('test');

        try {
            // Find all draft assignments where available_from <= now()
            $assignmentsToPublish = Assignment::query()
                ->where('status', AssignmentStatus::Draft->value)
                ->where('available_from', '<=', now())
                ->get();

            if ($assignmentsToPublish->isEmpty()) {
                $this->info('No assignments to publish at this time.');
                return self::SUCCESS;
            }

            $count = $assignmentsToPublish->count();

            if ($testMode) {
                $this->info("[TEST MODE] Found {$count} assignment(s) to publish:");
                foreach ($assignmentsToPublish as $assignment) {
                    $this->line("  - [{$assignment->id}] {$assignment->title} (available from: {$assignment->available_from})");
                }
                return self::SUCCESS;
            }

            // Publish each assignment in transaction
            DB::transaction(function () use ($assignmentsToPublish) {
                foreach ($assignmentsToPublish as $assignment) {
                    $assignment->update([
                        'status' => AssignmentStatus::Published->value,
                        'updated_at' => now(),
                    ]);
                }
            });

            $this->info("Successfully published {$count} assignment(s).");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error publishing assignments: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
