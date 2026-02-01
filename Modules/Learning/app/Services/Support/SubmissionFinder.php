<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Auth\Models\User;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;
use Modules\Learning\Models\Submission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubmissionFinder
{
    public function __construct(
        private readonly SubmissionRepositoryInterface $repository,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly ?ReviewModeServiceInterface $reviewModeService = null
    ) {}

    public function listForAssignment(Assignment $assignment, User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->listForAssignmentForIndex($assignment, $user, $filters);
    }

    public function listForAssignmentForIndex(Assignment $assignment, User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, (int) data_get($filters, 'per_page', 15));

        $query = QueryBuilder::for(Submission::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('is_late'),
                AllowedFilter::scope('score_range', 'filterByScoreRange'),
            ])
            ->allowedSorts(['submitted_at', 'created_at', 'score', 'status'])
            ->defaultSort('-submitted_at')
            ->where('assignment_id', $assignment->id)
            ->with(['user:id,name,email', 'grade:id,submission_id,score,status,released_at']);

        if ($user->hasRole('Student')) {
            $query->where('user_id', $user->id);
        }

        return $query->paginate($perPage);
    }

    public function getSubmission(int $submissionId, array $filters = []): Submission
    {
        return QueryBuilder::for(Submission::class)
            ->where('id', $submissionId)
            ->allowedIncludes([
                'assignment',
                'answers',
                'answers.question',
                'user',
                'enrollment',
                'grade',
                'files'
            ])
            ->firstOrFail();
    }

    public function getSubmissionDetail(Submission $submission, ?int $userId): array
    {
        $submission = $this->getSubmission($submission->id, []);
        $submission->loadMissing(['assignment', 'answers.question']);

        $visibility = $this->reviewModeService?->getVisibilityStatus($submission, $userId) ?? [];

        return [
            'submission' => $submission,
            'visibility' => $visibility,
        ];
    }

    public function getSubmissionQuestions(Submission $submission): Collection
    {
        $questionIds = $submission->question_set;

        if (empty($questionIds)) {
            $questions = $this->questionRepository->findByAssignment($submission->assignment_id);
        } else {
            $questions = Question::whereIn('id', $questionIds)
                ->with(['assignment', 'media'])
                ->get();

            $questions = $questions->sortBy(function ($question) use ($questionIds) {
                return array_search($question->id, $questionIds);
            })->values();
        }

        $submission->load('answers');
        $answers = $submission->answers->keyBy('question_id');

        $questions->each(function ($question) use ($answers) {
            $question->current_answer = $answers->get($question->id);
        });

        return $questions;
    }

    public function getSubmissionQuestionsPaginated(Submission $submission, int $perPage = 1): LengthAwarePaginator
    {
        $questionIds = $submission->question_set;

        if (empty($questionIds)) {
            $query = Question::where('assignment_id', $submission->assignment_id)
                ->with(['assignment', 'media'])
                ->orderBy('order');
        } else {
            $query = Question::whereIn('id', $questionIds)
                ->with(['assignment', 'media'])
                ->orderByRaw('array_position(ARRAY[' . implode(',', $questionIds) . '], id)');
        }

        $paginator = $query->paginate($perPage);

        $submission->load('answers');
        $answers = $submission->answers->keyBy('question_id');

        $paginator->getCollection()->each(function ($question) use ($answers) {
            $question->current_answer = $answers->get($question->id);
        });

        return $paginator;
    }

    public function searchSubmissions(string $query, array $filters = [], array $options = []): array
    {
        return $this->repository->search($query, $filters, $options);
    }
    
    public function listByAssignment(Assignment $assignment, array $filters = [])
    {
        return $this->repository->listForAssignment($assignment, null, $filters);
    }

    public function getHighestScoreSubmission(int $assignmentId, int $studentId): ?Submission
    {
        return $this->repository->findHighestScore($studentId, $assignmentId);
    }

    public function getSubmissionsWithHighestMarked(int $assignmentId, int $studentId): Collection
    {
        $submissions = $this->repository->findByStudentAndAssignment($studentId, $assignmentId);

        if ($submissions->isEmpty()) {
            return $submissions;
        }

        $highestScore = $submissions->max('score');

        return $submissions->map(function ($submission) use ($highestScore) {
            $submission->is_highest = $submission->score !== null && $submission->score === $highestScore;

            return $submission;
        });
    }
}
