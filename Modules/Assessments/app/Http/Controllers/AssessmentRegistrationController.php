<?php

namespace Modules\Assessments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Assessments\Http\Requests\RegisterAssessmentRequest;
use Modules\Assessments\Interfaces\AssessmentRegistrationServiceInterface;
use Modules\Assessments\Models\AssessmentRegistration;
use Modules\Assessments\Models\Exercise;
use Modules\Assessments\Support\DTOs\RegisterAssessmentDTO;
use Modules\Assessments\Support\Exceptions\AssessmentFullException;
use Modules\Assessments\Support\Exceptions\CancellationNotAllowedException;
use Modules\Assessments\Support\Exceptions\PrerequisitesNotMetException;

class AssessmentRegistrationController extends Controller
{
    public function __construct(
        private AssessmentRegistrationServiceInterface $registrationService
    ) {}

    /**
     * Register for an assessment.
     */
    public function register(RegisterAssessmentRequest $request, int $assessmentId): JsonResponse
    {
        try {
            $exercise = Exercise::findOrFail($assessmentId);
            $user = auth()->user();

            $dto = new RegisterAssessmentDTO(
                enrollmentId: $request->input('enrollment_id'),
                scheduledAt: $request->input('scheduled_at'),
                paymentAmount: $request->input('payment_amount'),
                notes: $request->input('notes')
            );

            $registration = $this->registrationService->register($user, $exercise, $dto);

            return ApiResponse::success(
                data: $registration->load(['user', 'exercise', 'enrollment']),
                message: 'Successfully registered for assessment',
                code: 201
            );
        } catch (PrerequisitesNotMetException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 422,
                errors: $e->getData()
            );
        } catch (AssessmentFullException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 422
            );
        }
    }

    /**
     * Check prerequisites for an assessment.
     */
    public function checkPrerequisites(int $assessmentId): JsonResponse
    {
        $exercise = Exercise::findOrFail($assessmentId);
        $user = auth()->user();

        $result = $this->registrationService->checkPrerequisites($user, $exercise);

        return ApiResponse::success(
            data: [
                'prerequisites_met' => $result->met,
                'missing_prerequisites' => $result->missingPrerequisites,
                'completed_prerequisites' => $result->completedPrerequisites,
            ],
            message: $result->met
                ? 'All prerequisites met'
                : 'Some prerequisites are not met'
        );
    }

    /**
     * Get available time slots for an assessment.
     */
    public function getAvailableSlots(Request $request, int $assessmentId): JsonResponse
    {
        $exercise = Exercise::findOrFail($assessmentId);

        // Get date range from request or default to next 30 days
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->addDays(30)->toDateString());

        // For now, return a simple structure
        // In a real implementation, this would query actual time slots
        $slots = [];

        if ($exercise->max_capacity) {
            // Generate sample slots (this should be replaced with actual slot logic)
            $currentDate = now()->parse($startDate);
            $end = now()->parse($endDate);

            while ($currentDate <= $end) {
                // Skip weekends for this example
                if ($currentDate->isWeekday()) {
                    // Morning slot
                    $morningSlot = $currentDate->copy()->setTime(9, 0);
                    $morningRegistrations = AssessmentRegistration::where('exercise_id', $exercise->id)
                        ->where('scheduled_at', $morningSlot)
                        ->whereNotIn('status', [
                            AssessmentRegistration::STATUS_CANCELLED,
                            AssessmentRegistration::STATUS_NO_SHOW,
                        ])
                        ->count();

                    $slots[] = [
                        'datetime' => $morningSlot->toIso8601String(),
                        'available' => $morningRegistrations < $exercise->max_capacity,
                        'capacity' => $exercise->max_capacity,
                        'registered' => $morningRegistrations,
                        'remaining' => max(0, $exercise->max_capacity - $morningRegistrations),
                    ];

                    // Afternoon slot
                    $afternoonSlot = $currentDate->copy()->setTime(14, 0);
                    $afternoonRegistrations = AssessmentRegistration::where('exercise_id', $exercise->id)
                        ->where('scheduled_at', $afternoonSlot)
                        ->whereNotIn('status', [
                            AssessmentRegistration::STATUS_CANCELLED,
                            AssessmentRegistration::STATUS_NO_SHOW,
                        ])
                        ->count();

                    $slots[] = [
                        'datetime' => $afternoonSlot->toIso8601String(),
                        'available' => $afternoonRegistrations < $exercise->max_capacity,
                        'capacity' => $exercise->max_capacity,
                        'registered' => $afternoonRegistrations,
                        'remaining' => max(0, $exercise->max_capacity - $afternoonRegistrations),
                    ];
                }

                $currentDate->addDay();
            }
        }

        return ApiResponse::success(
            data: $slots,
            message: 'Available slots retrieved successfully'
        );
    }

    /**
     * Cancel an assessment registration.
     */
    public function cancel(Request $request, int $registrationId): JsonResponse
    {
        try {
            $registration = AssessmentRegistration::findOrFail($registrationId);

            // Ensure the user owns this registration
            if ($registration->user_id !== auth()->id()) {
                return ApiResponse::error(
                    message: 'Unauthorized to cancel this registration',
                    code: 403
                );
            }

            $reason = $request->input('reason', '');
            $this->registrationService->cancel($registration, $reason);

            return ApiResponse::success(
                data: $registration->fresh(),
                message: 'Registration cancelled successfully'
            );
        } catch (CancellationNotAllowedException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                code: 422
            );
        }
    }
}
