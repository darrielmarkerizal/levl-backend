<?php

namespace Modules\Grading\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Grading\Contracts\Repositories\GradingRepositoryInterface;
use Modules\Grading\Models\Grade;

class GradingRepository implements GradingRepositoryInterface
{
    protected const DEFAULT_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title',
        'grader:id,name,email',
    ];

    protected const DETAILED_EAGER_LOAD = [
        'submission.user:id,name,email',
        'submission.assignment:id,title,deadline_at,review_mode',
        'submission.answers.question:id,type,content,weight,max_score',
        'grader:id,name,email',
    ];

    public function __construct(private readonly Grade $model) {}

    public function findById(int $id): ?Grade
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->find($id);
    }

    public function findByIdWithDetails(int $id): ?Grade
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->find($id);
    }

    public function findBySubmission(int $submissionId): ?Grade
    {
        return $this->model
            ->where('submission_id', $submissionId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->first();
    }

    public function findBySubmissionWithDetails(int $submissionId): ?Grade
    {
        return $this->model
            ->where('submission_id', $submissionId)
            ->with(self::DETAILED_EAGER_LOAD)
            ->first();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->paginate($perPage);
    }

    public function create(array $data): Grade
    {
        $grade = $this->model->create($data);
        return $grade->load(self::DEFAULT_EAGER_LOAD);
    }

    public function update(Grade $grade, array $data): Grade
    {
        $grade->update($data);
        return $grade->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function delete(Grade $grade): bool
    {
        return $grade->delete();
    }

    public function findPendingManualGrading(array $filters = []): Collection
    {
        $query = $this->model
            ->whereHas('submission', function ($q) {
                $q->where('state', 'pending_manual_grading');
            })
            ->with([
                'submission.user:id,name,email',
                'submission.assignment:id,title,deadline_at',
                'submission.answers' => function ($q) {
                    $q->whereNull('score')
                        ->orWhereHas('question', function ($q) {
                            $q->whereIn('type', ['essay', 'file_upload']);
                        });
                },
                'submission.answers.question:id,type,content,weight',
                'grader:id,name,email',
            ])
            ->orderBy('created_at', 'asc');

        if (isset($filters['assignment_id'])) {
            $query->whereHas('submission', function ($q) use ($filters) {
                $q->where('assignment_id', $filters['assignment_id']);
            });
        }

        if (isset($filters['student_id'])) {
            $query->whereHas('submission', function ($q) use ($filters) {
                $q->where('user_id', $filters['student_id']);
            });
        }

        return $query->get();
    }

    public function saveDraft(int $submissionId, array $data): void
    {
        $grade = $this->model->where('submission_id', $submissionId)->first();

        if ($grade) {
            $grade->update(array_merge($data, ['is_draft' => true]));
        } else {
            $this->model->create(array_merge($data, [
                'submission_id' => $submissionId,
                'is_draft' => true,
            ]));
        }
    }

    public function findByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    public function findByGrader(int $graderId): Collection
    {
        return $this->model
            ->where('graded_by', $graderId)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    public function findReleased(): Collection
    {
        return $this->model
            ->whereNotNull('released_at')
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('released_at')
            ->get();
    }

    public function findUnreleased(): Collection
    {
        return $this->model
            ->whereNull('released_at')
            ->where('is_draft', false)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }

    public function findOverridden(): Collection
    {
        return $this->model
            ->where('is_override', true)
            ->with(self::DEFAULT_EAGER_LOAD)
            ->orderByDesc('graded_at')
            ->get();
    }
}
