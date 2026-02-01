<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Support\Carbon;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionValidator
{
    public function __construct(
        private readonly ?OverrideRepositoryInterface $overrideRepository = null
    ) {}

    public function checkAttemptLimits(Assignment $assignment, int $studentId): array
    {
        if ($assignment->max_attempts === null) {
            return ['allowed' => true, 'remaining' => null];
        }

        $attemptCount = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();

        $remaining = $assignment->max_attempts - $attemptCount;

        if ($remaining <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => __('messages.submissions.max_attempts_reached'),
            ];
        }

        return ['allowed' => true, 'remaining' => $remaining];
    }

    public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array
    {
        if (! $assignment->retake_enabled) {
            $hasSubmitted = Submission::where('assignment_id', $assignment->id)
                ->where('user_id', $studentId)
                ->whereNotIn('state', [SubmissionState::InProgress->value])
                ->exists();

            if ($hasSubmitted) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'message' => __('messages.submissions.retake_not_enabled'),
                ];
            }
        }

        if ($assignment->max_attempts === null) {
            return ['allowed' => true, 'remaining' => null];
        }

        $attemptCount = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotIn('state', [SubmissionState::InProgress->value])
            ->count();

        $additionalAttempts = $this->getAdditionalAttempts($assignment->id, $studentId);
        $effectiveMaxAttempts = $assignment->max_attempts + $additionalAttempts;

        $remaining = $effectiveMaxAttempts - $attemptCount;

        if ($remaining <= 0) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => __('messages.submissions.max_attempts_reached'),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'has_override' => $additionalAttempts > 0,
            'additional_attempts' => $additionalAttempts,
        ];
    }

    public function checkCooldownPeriod(Assignment $assignment, int $studentId): array
    {
        if ($assignment->cooldown_minutes <= 0) {
            return ['allowed' => true, 'next_attempt_at' => null];
        }

        $lastSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $studentId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        if (! $lastSubmission) {
            return ['allowed' => true, 'next_attempt_at' => null];
        }

        $nextAttemptAt = $lastSubmission->submitted_at->copy()
            ->addMinutes($assignment->cooldown_minutes);

        if (now()->lt($nextAttemptAt)) {
            return [
                'allowed' => false,
                'next_attempt_at' => $nextAttemptAt,
                'message' => __('messages.submissions.cooldown_active', ['time' => $nextAttemptAt->toDateTimeString()]),
            ];
        }

        return ['allowed' => true, 'next_attempt_at' => null];
    }

    public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool
    {
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            return now()->lte($extendedDeadline);
        }

        return ! $assignment->isPastTolerance();
    }

    public function isSubmissionLate(Assignment $assignment, int $studentId): bool
    {
        $extendedDeadline = $this->getExtendedDeadline($assignment->id, $studentId);

        if ($extendedDeadline !== null) {
            return now()->gt($extendedDeadline);
        }

        return $assignment->isPastDeadline();
    }

    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        if ($this->overrideRepository === null) {
            return false;
        }

        return $this->overrideRepository->hasActiveOverride($assignmentId, $studentId, $type);
    }
    
    public function getDeadlineStatus(Assignment $assignment, int $userId): array
    {
         return [
            'allowed' => $this->checkDeadlineWithOverride($assignment, $userId),
            'deadline' => $assignment->deadline_at?->toIso8601String(),
            'tolerance_minutes' => $assignment->tolerance_minutes,
        ];
    }

    private function getExtendedDeadline(int $assignmentId, int $studentId): ?Carbon
    {
        if ($this->overrideRepository === null) {
            return null;
        }

        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Deadline
        );

        return $override?->getExtendedDeadline();
    }

    private function getAdditionalAttempts(int $assignmentId, int $studentId): int
    {
        if ($this->overrideRepository === null) {
            return 0;
        }

        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Attempts
        );

        return $override?->getAdditionalAttempts() ?? 0;
    }
}
