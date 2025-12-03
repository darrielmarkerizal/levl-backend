<?php

namespace Modules\Assessments\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\Assessments\Events\AssessmentRegistered;
use Modules\Assessments\Mail\AssessmentConfirmationMail;

class SendAssessmentConfirmation
{
    /**
     * Handle the event.
     */
    public function handle(AssessmentRegistered $event): void
    {
        $registration = $event->registration;

        // Send confirmation email
        Mail::to($registration->user->email)
            ->send(new AssessmentConfirmationMail($registration));

        // Update confirmation_sent_at timestamp
        $registration->update([
            'confirmation_sent_at' => now(),
        ]);
    }
}
