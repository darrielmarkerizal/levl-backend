<?php

namespace Modules\Assessments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Assessments\Models\AssessmentRegistration;

class AssessmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public AssessmentRegistration $registration;

    /**
     * Create a new message instance.
     */
    public function __construct(AssessmentRegistration $registration)
    {
        $this->registration = $registration;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assessment Registration Confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'assessments::emails.assessment-confirmation',
            with: [
                'registration' => $this->registration,
                'user' => $this->registration->user,
                'exercise' => $this->registration->exercise,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
