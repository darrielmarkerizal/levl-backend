<?php

declare(strict_types=1);

namespace Modules\Common\Services;

use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Common\Contracts\Repositories\AuditRepositoryInterface;
use Modules\Common\Contracts\Services\AuditServiceInterface;
use Modules\Common\Models\AuditLog;

use Modules\Grading\Models\Grade;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;

/**
 * Service for comprehensive audit logging of all critical assessment and grading operations.
 *
 * This service provides immutable logging for compliance and dispute resolution.
 * All log entries are append-only and cannot be modified or deleted.
 *
 * Requirements: 20.1, 20.2, 20.3, 20.4, 20.5, 20.7
 */
class AssessmentAuditService implements AuditServiceInterface
{
    /**
     * Action constants for audit logging.
     */
    public const ACTION_SUBMISSION_CREATED = 'submission_created';

    public const ACTION_STATE_TRANSITION = 'state_transition';

    public const ACTION_GRADING = 'grading';

    public const ACTION_ANSWER_KEY_CHANGE = 'answer_key_change';

    public const ACTION_GRADE_OVERRIDE = 'grade_override';



    public const ACTION_OVERRIDE_GRANT = 'override_grant';

    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository
    ) {}

    /**
     * Log submission creation.
     *
     * Requirements: 20.1
     *
     * @param  Submission  $submission  The created submission
     */
    public function logSubmissionCreated(Submission $submission): void
    {
        AuditLog::logAction(
            action: self::ACTION_SUBMISSION_CREATED,
            subject: $submission,
            actor: User::find($submission->user_id),
            context: $this->buildSubmissionContext($submission)
        );
    }

    public function logStateTransition(Submission $submission, string $oldState, string $newState, int $actorId): void
    {
        AuditLog::logAction(
            action: self::ACTION_STATE_TRANSITION,
            subject: $submission,
            actor: User::find($actorId),
            context: $this->buildStateTransitionContext($submission, $oldState, $newState, $actorId)
        );
    }

    public function logGrading(Grade $grade, int $instructorId): void
    {
        AuditLog::logAction(
            action: self::ACTION_GRADING,
            subject: $grade,
            actor: User::find($instructorId),
            context: $this->buildGradingContext($grade, $instructorId)
        );
    }

    public function logAnswerKeyChange(Question $question, array $oldKey, array $newKey, int $instructorId): void
    {
        AuditLog::logAction(
            action: self::ACTION_ANSWER_KEY_CHANGE,
            subject: $question,
            actor: User::find($instructorId),
            context: $this->buildAnswerKeyChangeContext($question, $oldKey, $newKey, $instructorId)
        );
    }

    public function logGradeOverride(Grade $grade, float $oldScore, float $newScore, string $reason, int $instructorId): void
    {
        AuditLog::logAction(
            action: self::ACTION_GRADE_OVERRIDE,
            subject: $grade,
            actor: User::find($instructorId),
            context: $this->buildGradeOverrideContext($grade, $oldScore, $newScore, $reason, $instructorId)
        );
    }

    public function logOverrideGrant(int $assignmentId, int $studentId, string $overrideType, string $reason, int $instructorId): void
    {
        AuditLog::logAction(
            action: self::ACTION_OVERRIDE_GRANT,
            subject: null,
            actor: User::find($instructorId),
            context: $this->buildOverrideGrantContext($assignmentId, $studentId, $overrideType, $reason, $instructorId)
        );
    }

    public function search(array $filters): Collection
    {
        return $this->auditRepository->search($filters);
    }

    private function buildSubmissionContext(Submission $submission): array
    {
        return [
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->user_id,
            'attempt_number' => $submission->attempt_number,
            'state' => $submission->state ? $submission->state->value : 'in_progress',
            'is_late' => $submission->is_late ?? false,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
        ];
    }

    private function buildStateTransitionContext(Submission $submission, string $oldState, string $newState, int $actorId): array
    {
        return [
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->user_id,
            'old_state' => $oldState,
            'new_state' => $newState,
            'actor_id' => $actorId,
            'transitioned_at' => now()->toIso8601String(),
        ];
    }

    private function buildGradingContext(Grade $grade, int $instructorId): array
    {
        return [
            'submission_id' => $grade->submission_id,
            'student_id' => $grade->user_id,
            'instructor_id' => $instructorId,
            'score' => (float) $grade->score,
            'max_score' => (float) ($grade->max_score ?? 100),
            'is_draft' => $grade->is_draft ?? false,
            'feedback' => $grade->feedback,
            'graded_at' => $grade->graded_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    private function buildAnswerKeyChangeContext(Question $question, array $oldKey, array $newKey, int $instructorId): array
    {
        return [
            'assignment_id' => $question->assignment_id,
            'question_id' => $question->id,
            'question_type' => $question->type instanceof \Modules\Learning\Enums\QuestionType ? $question->type->value : $question->type,
            'instructor_id' => $instructorId,
            'old_answer_key' => $oldKey,
            'new_answer_key' => $newKey,
            'changed_at' => now()->toIso8601String(),
        ];
    }

    private function buildGradeOverrideContext(Grade $grade, float $oldScore, float $newScore, string $reason, int $instructorId): array
    {
        return [
            'submission_id' => $grade->submission_id,
            'student_id' => $grade->user_id,
            'instructor_id' => $instructorId,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'reason' => $reason,
            'overridden_at' => now()->toIso8601String(),
        ];
    }

    private function buildOverrideGrantContext(int $assignmentId, int $studentId, string $overrideType, string $reason, int $instructorId): array
    {
        return [
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
            'instructor_id' => $instructorId,
            'override_type' => $overrideType,
            'reason' => $reason,
            'granted_at' => now()->toIso8601String(),
        ];
    }
}
