<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Common\Models\SystemSetting;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Models\Enrollment;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Enums\SubmissionState;
use Modules\Learning\Enums\SubmissionStatus;
use Modules\Learning\Events\SubmissionCreated;
use Modules\Learning\Exceptions\SubmissionException;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Schemes\Models\Lesson;

class SubmissionCreationProcessor
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $repository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly ?QuestionServiceInterface $questionService = null,
    ) {}

    public function create(Assignment $assignment, int $userId, array $data): Submission
    {
        return DB::transaction(function () use ($assignment, $userId, $data) {
            $lesson = $assignment->lesson;
            if (! $lesson) {
                throw SubmissionException::notAllowed(__('messages.submissions.assignment_no_lesson'));
            }

            $enrollment = $this->getEnrollmentForLesson($lesson, $userId);
            if (! $enrollment) {
                throw SubmissionException::notAllowed(__('messages.submissions.not_enrolled'));
            }

            if (! $assignment->isAvailable()) {
                throw SubmissionException::notAllowed(__('messages.submissions.assignment_unavailable'));
            }

            $existingSubmission = $this->repository->latestCommittedSubmission($assignment, $userId);

            $isResubmission = $existingSubmission !== null;
            if ($isResubmission && ! $this->isResubmitAllowed($assignment)) {
                throw SubmissionException::notAllowed(__('messages.submissions.resubmission_not_allowed'));
            }

            $attemptNumber = $isResubmission ? ($existingSubmission->attempt_number + 1) : 1;
            $isLate = $assignment->isPastDeadline();

            if ($isResubmission && $existingSubmission) {
                $this->repository->delete($existingSubmission);
            }

            $questionSet = $this->generateQuestionSet($assignment->id);

            $submission = $this->repository->create([
                'assignment_id' => $assignment->id,
                'user_id' => $userId,
                'enrollment_id' => $enrollment->id,
                'answer_text' => $data['answer_text'] ?? null,
                'status' => $isLate ? SubmissionStatus::Late->value : SubmissionStatus::Submitted->value,
                'attempt_number' => $attemptNumber,
                'is_late' => $isLate,
                'is_resubmission' => $isResubmission,
                'previous_submission_id' => null,
                'submitted_at' => Carbon::now(),
                'question_set' => $questionSet,
            ]);

            $this->enrollmentRepository->incrementLessonProgress($enrollment->id, $lesson->id);

            SubmissionCreated::dispatch($submission);

            return $submission->fresh(['assignment', 'user', 'enrollment', 'files', 'grade']);
        });
    }

    public function startSubmission(
        int $assignmentId, 
        int $studentId, 
        SubmissionValidator $validator
    ): Submission {
        $assignment = Assignment::findOrFail($assignmentId);

        if (! $validator->checkDeadlineWithOverride($assignment, $studentId)) {
            throw SubmissionException::deadlinePassed();
        }

        $attemptCheck = $validator->checkAttemptLimitsWithOverride($assignment, $studentId);
        if (! $attemptCheck['allowed']) {
            throw SubmissionException::maxAttemptsReached(
                $attemptCheck['message'] ?? __('messages.submissions.max_attempts_reached')
            );
        }

        $cooldownCheck = $validator->checkCooldownPeriod($assignment, $studentId);
        if (! $cooldownCheck['allowed']) {
            throw SubmissionException::notAllowed(
                $attemptCheck['message'] ?? __('messages.submissions.cooldown_active', ['time' => $cooldownCheck['next_attempt_at']->toDateTimeString()])
            );
        }

        $questionSet = $this->generateQuestionSet($assignmentId);

        $attemptNumber = Submission::where('assignment_id', $assignmentId)
            ->where('user_id', $studentId)
            ->count() + 1;

        return DB::transaction(function () use ($assignmentId, $studentId, $attemptNumber, $questionSet) {
            $submission = $this->repository->create([
                'assignment_id' => $assignmentId,
                'user_id' => $studentId,
                'state' => SubmissionState::InProgress->value,
                'status' => SubmissionStatus::Draft->value,
                'attempt_number' => $attemptNumber,
                'question_set' => $questionSet,
            ]);

            return $submission->fresh(['assignment', 'user']);
        });
    }

    private function getEnrollmentForLesson(Lesson $lesson, int $userId): ?Enrollment
    {
        $lesson->loadMissing('unit.course');

        if (! $lesson->unit || ! $lesson->unit->course) {
            return null;
        }

        return $this->enrollmentRepository->findActiveByUserAndCourse($userId, $lesson->unit->course->id);
    }

    private function isResubmitAllowed(Assignment $assignment): bool
    {
        return $assignment->allow_resubmit !== null
            ? (bool) $assignment->allow_resubmit
            : SystemSetting::get('learning.allow_resubmit', true);
    }

    private function generateQuestionSet(int $assignmentId): ?array
    {
        if ($this->questionService) {
            $seed = random_int(1, PHP_INT_MAX);
            $questions = $this->questionService->generateQuestionSet($assignmentId, $seed);
            return $questions->pluck('id')->toArray();
        }
        return null;
    }
}
