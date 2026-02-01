<?php

declare(strict_types=1);

namespace Modules\Learning\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Enums\OverrideType;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Services\Support\SubmissionFinder;
use Modules\Learning\Services\Support\SubmissionLifecycleProcessor;
use Modules\Learning\Services\Support\SubmissionValidator;

class SubmissionService implements SubmissionServiceInterface
{
    public function __construct(
        private readonly SubmissionFinder $finder,
        private readonly SubmissionLifecycleProcessor $lifecycleProcessor,
        private readonly SubmissionValidator $validator,
        private readonly QuestionRepositoryInterface $questionRepository
    ) {}

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return $this->lifecycleProcessor->create($assignment, $userId, $data);
    }

    public function startSubmission(int $assignmentId, int $studentId): Submission
    {
        return $this->lifecycleProcessor->startSubmission($assignmentId, $studentId, $this->validator);
    }

    public function update(Submission $submission, array $data): Submission
    {
        return $this->lifecycleProcessor->update($submission, $data);
    }

    public function saveAnswer(Submission $submission, int $questionId, mixed $answer): Answer
    {
        return $this->lifecycleProcessor->saveAnswer($submission, $questionId, $answer, $this->validator);
    }

    public function submitAnswers(int $submissionId, array $answers): Submission
    {
        return $this->lifecycleProcessor->submitAnswers($submissionId, $answers, $this->validator, $this->questionRepository);
    }

    public function delete(Submission $submission): bool
    {
        return $this->lifecycleProcessor->delete($submission);
    }

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->listForAssignment($assignment, $user, $filters);
    }

    public function listForAssignmentForIndex(Assignment $assignment, User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->finder->listForIndex($assignment, $user, $filters);
    }

    public function getSubmission(int $submissionId, array $filters = []): Submission
    {
        return $this->finder->getSubmission($submissionId, $filters);
    }

    public function getSubmissionDetail(Submission $submission, ?int $userId): array
    {
        return $this->finder->getSubmissionDetail($submission, $userId);
    }

    public function getSubmissionQuestions(Submission $submission): Collection
    {
        return $this->finder->getSubmissionQuestions($submission);
    }

    public function getSubmissionQuestionsPaginated(Submission $submission, int $perPage = 1): LengthAwarePaginator
    {
        return $this->finder->getSubmissionQuestionsPaginated($submission, $perPage);
    }

    public function searchSubmissions(string $query, array $filters = [], array $options = []): array
    {
        return $this->finder->searchSubmissions($query, $filters, $options);
    }

    public function listByAssignment(Assignment $assignment, array $filters = [])
    {
        return $this->finder->listByAssignment($assignment, $filters);
    }

    public function checkAttemptLimits(Assignment $assignment, int $studentId): array
    {
        return $this->validator->checkAttemptLimits($assignment, $studentId);
    }

    public function checkAttemptLimitsWithOverride(Assignment $assignment, int $studentId): array
    {
        return $this->validator->checkAttemptLimitsWithOverride($assignment, $studentId);
    }

    public function checkCooldownPeriod(Assignment $assignment, int $studentId): array
    {
        return $this->validator->checkCooldownPeriod($assignment, $studentId);
    }

    public function checkDeadlineWithOverride(Assignment $assignment, int $studentId): bool
    {
        return $this->validator->checkDeadlineWithOverride($assignment, $studentId);
    }

    public function isSubmissionLate(Assignment $assignment, int $studentId): bool
    {
        return $this->validator->isSubmissionLate($assignment, $studentId);
    }

    public function hasActiveOverride(int $assignmentId, int $studentId, OverrideType $type): bool
    {
        return $this->validator->hasActiveOverride($assignmentId, $studentId, $type);
    }

    public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission
    {
        return $this->finder->getHighestScoreSubmission($assignmentId, $studentId);
    }
    
    public function getSubmissionsWithHighestMarked(int $assignmentId, int $studentId): Collection
    {
        return $this->finder->getSubmissionsWithHighestMarked($assignmentId, $studentId);
    }

    public function updateSubmissionScore(Submission $submission, float $score): Submission
    {
        return $this->lifecycleProcessor->updateSubmissionScore($submission, $score);
    }
    
    public function getDeadlineStatus(Assignment $assignment, int $userId): array
    {
        return $this->validator->getDeadlineStatus($assignment, $userId);
    }
    
    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission
    {
        return $this->lifecycleProcessor->grade($submission, $score, $gradedBy, $feedback);
    }
}
