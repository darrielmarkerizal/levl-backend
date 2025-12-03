<?php

namespace Modules\Assessments\Interfaces;

use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Support\DTOs\PrerequisiteCheckResult;
use Modules\Assessments\Support\DTOs\RegisterAssessmentDTO;
use Modules\Auth\Models\User;

interface AssessmentRegistrationServiceInterface
{
    /**
     * Register user for assessment.
     *
     * @throws \Modules\Assessments\Support\Exceptions\PrerequisitesNotMetException
     */
    public function register(
        User $user,
        Exercise $exercise,
        RegisterAssessmentDTO $dto
    ): AssessmentRegistration;

    /**
     * Check if user meets prerequisites.
     */
    public function checkPrerequisites(User $user, Exercise $exercise): PrerequisiteCheckResult;

    /**
     * Cancel registration.
     *
     * @throws \Modules\Assessments\Support\Exceptions\CancellationNotAllowedException
     */
    public function cancel(AssessmentRegistration $registration, string $reason = ''): bool;
}
