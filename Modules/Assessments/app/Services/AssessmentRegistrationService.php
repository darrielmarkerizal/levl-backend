<?php

namespace Modules\Assessments\Services;

use Illuminate\Support\Facades\DB;
use Modules\Assessments\Events\AssessmentRegistered;
use Modules\Assessments\Interfaces\AssessmentRegistrationServiceInterface;
use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Support\DTOs\PrerequisiteCheckResult;
use Modules\Assessments\Support\DTOs\RegisterAssessmentDTO;
use Modules\Assessments\Support\Exceptions\AssessmentFullException;
use Modules\Assessments\Support\Exceptions\PrerequisitesNotMetException;
use Modules\Auth\Models\User;
use Modules\Enrollments\Models\Enrollment;

class AssessmentRegistrationService implements AssessmentRegistrationServiceInterface
{
    /**
     * Register user for assessment.
     *
     * @throws PrerequisitesNotMetException
     * @throws AssessmentFullException
     */
    public function register(
        User $user,
        Exercise $exercise,
        RegisterAssessmentDTO $dto
    ): AssessmentRegistration {
        // Check prerequisites
        $prerequisiteCheck = $this->checkPrerequisites($user, $exercise);

        if (! $prerequisiteCheck->met) {
            throw new PrerequisitesNotMetException($prerequisiteCheck->missingPrerequisites);
        }

        // Check slot availability if scheduled_at is provided
        if ($dto->scheduledAt && $exercise->max_capacity) {
            $this->checkSlotAvailability($exercise, $dto->scheduledAt);
        }

        // Create registration
        return DB::transaction(function () use ($user, $exercise, $dto) {
            $data = [
                'user_id' => $user->id,
                'exercise_id' => $exercise->id,
                'enrollment_id' => $dto->enrollmentId,
                'scheduled_at' => $dto->scheduledAt,
                'status' => AssessmentRegistration::STATUS_CONFIRMED,
                'prerequisites_met' => true,
                'prerequisites_checked_at' => now(),
                'notes' => $dto->notes,
            ];

            // Only set payment fields if payment amount is provided
            if ($dto->paymentAmount) {
                $data['payment_amount'] = $dto->paymentAmount;
                $data['payment_status'] = AssessmentRegistration::PAYMENT_PENDING;
            }

            $registration = AssessmentRegistration::create($data);

            // Fire event for confirmation notification
            event(new AssessmentRegistered($registration));

            return $registration;
        });
    }

    /**
     * Check if user meets prerequisites.
     */
    public function checkPrerequisites(User $user, Exercise $exercise): PrerequisiteCheckResult
    {
        $missingPrerequisites = [];
        $completedPrerequisites = [];

        // Get the scope (course/unit/lesson) for the exercise
        $scope = $this->getExerciseScope($exercise);

        if (! $scope) {
            // No scope means no prerequisites
            return new PrerequisiteCheckResult(
                met: true,
                missingPrerequisites: [],
                completedPrerequisites: []
            );
        }

        // Check if user has enrollment for the course
        $enrollment = $this->getUserEnrollment($user, $scope, $exercise);

        if (! $enrollment) {
            $missingPrerequisites[] = 'Course enrollment required';
        } else {
            $completedPrerequisites[] = 'Course enrollment';

            // Check if enrollment is active
            if ($enrollment->status !== 'active' && $enrollment->status !== 'completed') {
                $missingPrerequisites[] = 'Active enrollment status required';
            } else {
                $completedPrerequisites[] = 'Active enrollment status';
            }
        }

        // Check course-specific prerequisites if scope is a course
        if ($scope instanceof \Modules\Schemes\Models\Course && $scope->prereq_text) {
            // For now, we'll just check if the text exists
            // In a real implementation, this would parse and check specific prerequisites
            $completedPrerequisites[] = 'Course prerequisites reviewed';
        }

        $met = empty($missingPrerequisites);

        return new PrerequisiteCheckResult(
            met: $met,
            missingPrerequisites: $missingPrerequisites,
            completedPrerequisites: $completedPrerequisites
        );
    }

    /**
     * Get the scope (course/unit/lesson) for an exercise.
     */
    private function getExerciseScope(Exercise $exercise)
    {
        return match ($exercise->scope_type) {
            'course' => \Modules\Schemes\Models\Course::find($exercise->scope_id),
            'unit' => \Modules\Schemes\Models\Unit::find($exercise->scope_id),
            'lesson' => \Modules\Schemes\Models\Lesson::find($exercise->scope_id),
            default => null,
        };
    }

    /**
     * Get user's enrollment for the course related to the exercise.
     */
    private function getUserEnrollment(User $user, $scope, Exercise $exercise): ?Enrollment
    {
        // Determine the course ID based on scope type
        $courseId = match (true) {
            $scope instanceof \Modules\Schemes\Models\Course => $scope->id,
            $scope instanceof \Modules\Schemes\Models\Unit => $scope->course_id,
            $scope instanceof \Modules\Schemes\Models\Lesson => $scope->unit?->course_id,
            default => null,
        };

        if (! $courseId) {
            return null;
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * Check if slot is available for the scheduled time.
     *
     * @throws AssessmentFullException
     */
    private function checkSlotAvailability(Exercise $exercise, ?string $scheduledAt): void
    {
        if (! $scheduledAt || ! $exercise->max_capacity) {
            return;
        }

        // Count existing registrations for this slot
        $existingRegistrations = AssessmentRegistration::where('exercise_id', $exercise->id)
            ->where('scheduled_at', $scheduledAt)
            ->whereNotIn('status', [
                AssessmentRegistration::STATUS_CANCELLED,
                AssessmentRegistration::STATUS_NO_SHOW,
            ])
            ->count();

        if ($existingRegistrations >= $exercise->max_capacity) {
            throw new AssessmentFullException(
                "Assessment slot is full. Maximum capacity of {$exercise->max_capacity} reached."
            );
        }
    }

    /**
     * Cancel registration.
     *
     * @throws CancellationNotAllowedException
     */
    public function cancel(AssessmentRegistration $registration, string $reason = ''): bool
    {
        // Check if registration can be cancelled
        if (in_array($registration->status, [
            AssessmentRegistration::STATUS_COMPLETED,
            AssessmentRegistration::STATUS_CANCELLED,
            AssessmentRegistration::STATUS_IN_PROGRESS,
        ])) {
            throw new CancellationNotAllowedException(
                "Cannot cancel registration with status: {$registration->status}"
            );
        }

        // Check cancellation timeframe (must be at least 24 hours before scheduled time)
        if ($registration->scheduled_at) {
            $hoursUntilAssessment = now()->diffInHours($registration->scheduled_at, false);

            if ($hoursUntilAssessment < 24) {
                throw new CancellationNotAllowedException(
                    'Cancellation must be made at least 24 hours before the scheduled assessment time'
                );
            }
        }

        return DB::transaction(function () use ($registration, $reason) {
            // Update status to cancelled
            $registration->update([
                'status' => AssessmentRegistration::STATUS_CANCELLED,
                'notes' => $registration->notes
                    ? $registration->notes."\n\nCancellation reason: ".$reason
                    : 'Cancellation reason: '.$reason,
            ]);

            // Handle payment refund if applicable
            if ($registration->payment_status === AssessmentRegistration::PAYMENT_PAID) {
                $registration->update([
                    'payment_status' => AssessmentRegistration::PAYMENT_REFUNDED,
                ]);
            }

            return true;
        });
    }
}
