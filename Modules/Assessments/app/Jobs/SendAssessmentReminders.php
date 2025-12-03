<?php

namespace Modules\Assessments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Assessments\Mail\AssessmentReminderMail;
use Modules\Assessments\Models\AssessmentRegistration;

class SendAssessmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find registrations scheduled for 24 hours from now (with 1 hour buffer)
        $targetTime = now()->addHours(24);
        $startTime = $targetTime->copy()->subHour();
        $endTime = $targetTime->copy()->addHour();

        $registrations = AssessmentRegistration::with(['user', 'exercise'])
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereNull('reminder_sent_at')
            ->whereIn('status', [
                AssessmentRegistration::STATUS_CONFIRMED,
                AssessmentRegistration::STATUS_SCHEDULED,
            ])
            ->get();

        foreach ($registrations as $registration) {
            // Send reminder email
            Mail::to($registration->user->email)
                ->send(new AssessmentReminderMail($registration));

            // Update reminder_sent_at timestamp
            $registration->update([
                'reminder_sent_at' => now(),
            ]);
        }
    }
}
