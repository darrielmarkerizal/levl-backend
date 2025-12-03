<?php

namespace Modules\Assessments\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assessments\Models\AssessmentRegistration;

class AssessmentRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AssessmentRegistration $registration;

    /**
     * Create a new event instance.
     */
    public function __construct(AssessmentRegistration $registration)
    {
        $this->registration = $registration;
    }
}
