<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Models\Answer;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;

class SubmissionLifecycleProcessor
{
    public function __construct(
        private readonly SubmissionCreationProcessor $creationProcessor,
        private readonly SubmissionCompletionProcessor $completionProcessor
    ) {}

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return $this->creationProcessor->create($assignment, $userId, $data);
    }

    public function startSubmission(
        int $assignmentId, 
        int $studentId, 
        SubmissionValidator $validator
    ): Submission {
        return $this->creationProcessor->startSubmission($assignmentId, $studentId, $validator);
    }

    public function update(Submission $submission, array $data): Submission
    {
        return $this->completionProcessor->update($submission, $data);
    }

    public function grade(Submission $submission, int $score, int $gradedBy, ?string $feedback = null): Submission
    {
        return $this->completionProcessor->grade($submission, $score, $gradedBy, $feedback);
    }
    
    public function saveAnswer(Submission $submission, int $questionId, mixed $answer, SubmissionValidator $validator): Answer
    {
        return $this->completionProcessor->saveAnswer($submission, $questionId, $answer, $validator);
    }

    public function submitAnswers(int $submissionId, array $answers, SubmissionValidator $validator, QuestionRepositoryInterface $questionRepo): Submission
    {
        return $this->completionProcessor->submitAnswers($submissionId, $answers, $validator, $questionRepo);
    }

    public function updateSubmissionScore(Submission $submission, float $score): Submission
    {
        return $this->completionProcessor->updateSubmissionScore($submission, $score);
    }
    
    public function delete(Submission $submission): bool
    {
        return $this->completionProcessor->delete($submission);
    }
}
