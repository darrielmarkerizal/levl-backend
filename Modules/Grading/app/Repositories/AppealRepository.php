<?php

declare(strict_types=1);

namespace Modules\Grading\Repositories;

use Illuminate\Support\Collection;
use Modules\Grading\Contracts\Repositories\AppealRepositoryInterface;
use Modules\Grading\Enums\AppealStatus;
use Modules\Grading\Models\Appeal;
use Modules\Learning\Models\Assignment;

class AppealRepository implements AppealRepositoryInterface
{
    protected const DEFAULT_EAGER_LOAD = [
        'submission.assignment:id,title,deadline_at',
        'submission.user:id,name,email',
        'student:id,name,email',
        'reviewer:id,name,email',
    ];

    protected const DETAILED_EAGER_LOAD = [
        'submission.assignment:id,title,deadline_at,tolerance_minutes',
        'submission.user:id,name,email',
        'submission.answers.question:id,type,content,weight',
        'submission.grade',
        'student:id,name,email',
        'reviewer:id,name,email',
    ];

    public function __construct(
        private readonly Appeal $model
    ) {}

    public function create(array $data): Appeal
    {
        $appeal = $this->model->newQuery()->create($data);
        return $appeal->load(self::DEFAULT_EAGER_LOAD);
    }

    public function update(int $id, array $data): Appeal
    {
        $appeal = $this->model->newQuery()->findOrFail($id);
        $appeal->update($data);

        return $appeal->fresh()->load(self::DEFAULT_EAGER_LOAD);
    }

    public function findById(int $id): ?Appeal
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->find($id);
    }

    public function findByIdWithDetails(int $id): ?Appeal
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->find($id);
    }

    public function findPending(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Pending)
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    public function findBySubmission(int $submissionId): ?Appeal
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('submission_id', $submissionId)
            ->first();
    }

    public function findBySubmissionWithDetails(int $submissionId): ?Appeal
    {
        return $this->model
            ->with(self::DETAILED_EAGER_LOAD)
            ->where('submission_id', $submissionId)
            ->first();
    }

    public function findPendingForInstructor(int $instructorId): Collection
    {
        $assignmentIds = Assignment::where('created_by', $instructorId)->pluck('id');

        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Pending)
            ->whereHas('submission', function ($query) use ($assignmentIds) {
                $query->whereIn('assignment_id', $assignmentIds);
            })
            ->orderBy('submitted_at', 'asc')
            ->get();
    }

    public function findByStudent(int $studentId): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('student_id', $studentId)
            ->orderByDesc('submitted_at')
            ->get();
    }

    public function findByReviewer(int $reviewerId): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('reviewer_id', $reviewerId)
            ->orderByDesc('decided_at')
            ->get();
    }

    public function findApproved(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Approved)
            ->orderByDesc('decided_at')
            ->get();
    }

    public function findDenied(): Collection
    {
        return $this->model
            ->with(self::DEFAULT_EAGER_LOAD)
            ->where('status', AppealStatus::Denied)
            ->orderByDesc('decided_at')
            ->get();
    }
}
