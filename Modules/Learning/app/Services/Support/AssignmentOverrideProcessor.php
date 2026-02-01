<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Events\OverrideGranted;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Override;

class AssignmentOverrideProcessor
{
    public function __construct(
        private readonly OverrideRepositoryInterface $overrideRepository,
        private readonly AssignmentRepositoryInterface $assignmentRepository
    ) {}

    public function grantOverride(
        int $assignmentId,
        int $studentId,
        string $overrideType,
        string $reason,
        array $value = [],
        ?int $grantorId = null
    ): Override {
        if (empty(trim($reason))) {
            throw new \InvalidArgumentException(__('messages.assignments.override_reason_required'));
        }

        $type = OverrideType::tryFrom($overrideType);
        if ($type === null) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_override_type', ['type' => $overrideType, 'valid_types' => implode(', ', OverrideType::values())])
            );
        }

        $assignment = $this->assignmentRepository->findByIdOrFail($assignmentId);

        $validatedValue = $this->validateOverrideValue($type, $value, $assignment);

        return DB::transaction(function () use ($assignmentId, $studentId, $grantorId, $type, $reason, $validatedValue) {
            $override = $this->overrideRepository->create([
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'grantor_id' => $grantorId,
                'type' => $type->value,
                'reason' => trim($reason),
                'value' => $validatedValue,
                'granted_at' => Carbon::now(),
                'expires_at' => $validatedValue['expires_at'] ?? null,
            ]);

            OverrideGranted::dispatch($override, $grantorId);

            return $override->load(['student', 'grantor']);
        });
    }

    public function getOverridesForAssignment(int $assignmentId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->overrideRepository->getOverridesForAssignment($assignmentId);
    }

    public function hasPrerequisiteOverride(int $assignmentId, int $studentId): bool
    {
        return $this->overrideRepository->hasActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Prerequisite
        );
    }

    public function getBypassedPrerequisiteIds(int $assignmentId, int $studentId): array
    {
        $override = $this->overrideRepository->findActiveOverride(
            $assignmentId,
            $studentId,
            OverrideType::Prerequisite
        );

        if ($override === null) {
            return [];
        }

        return $override->getBypassedPrerequisites() ?? [];
    }

    private function validateOverrideValue(OverrideType $type, array $value, Assignment $assignment): array
    {
        return match ($type) {
            OverrideType::Prerequisite => $this->validatePrerequisiteOverrideValue($value, $assignment),
            OverrideType::Attempts => $this->validateAttemptsOverrideValue($value),
            OverrideType::Deadline => $this->validateDeadlineOverrideValue($value, $assignment),
        };
    }

    private function validatePrerequisiteOverrideValue(array $value, Assignment $assignment): array
    {
        $bypassedPrerequisites = $value['bypassed_prerequisites'] ?? [];

        if (! empty($bypassedPrerequisites)) {
            $validPrerequisiteIds = $assignment->prerequisites()->pluck('id')->toArray();
            $invalidIds = array_diff($bypassedPrerequisites, $validPrerequisiteIds);

            if (! empty($invalidIds)) {
                throw new \InvalidArgumentException(
                    __('messages.assignments.invalid_prerequisites_list', ['ids' => implode(', ', $invalidIds)])
                );
            }
        }

        return [
            'bypassed_prerequisites' => $bypassedPrerequisites,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

    private function validateAttemptsOverrideValue(array $value): array
    {
        $additionalAttempts = $value['additional_attempts'] ?? null;

        if ($additionalAttempts === null || ! is_int($additionalAttempts) || $additionalAttempts < 1) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_additional_attempts')
            );
        }

        return [
            'additional_attempts' => $additionalAttempts,
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }

    private function validateDeadlineOverrideValue(array $value, Assignment $assignment): array
    {
        $extendedDeadline = $value['extended_deadline'] ?? null;

        if ($extendedDeadline === null) {
            throw new \InvalidArgumentException(
                __('messages.assignments.deadline_extension_required')
            );
        }

        try {
            $deadline = Carbon::parse($extendedDeadline);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                __('messages.assignments.deadline_extension_invalid_format')
            );
        }

        if ($deadline->isPast()) {
            throw new \InvalidArgumentException(
                __('messages.assignments.invalid_deadline_extension')
            );
        }

        return [
            'extended_deadline' => $deadline->toIso8601String(),
            'expires_at' => $value['expires_at'] ?? null,
        ];
    }
}
