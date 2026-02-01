<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Support\Facades\DB;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Enums\AssignmentStatus;
use Modules\Learning\Exceptions\AssignmentException;
use Modules\Learning\Models\Assignment;

class AssignmentDuplicator
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $repository
    ) {}

    public function duplicateAssignment(int $assignmentId, int $userId, array $overrides = []): Assignment
    {
        return DB::transaction(function () use ($assignmentId, $userId, $overrides) {
            $overrides['created_by'] = $userId;
            $original = $this->repository->findForDuplication($assignmentId);
            
            if (!$original) {
                throw AssignmentException::notFound();
            }

            $assignmentData = $this->prepareAssignmentDataForDuplication($original, $overrides);

            $newAssignment = $this->repository->create($assignmentData);

            $this->duplicateQuestions($original, $newAssignment);

            $this->duplicatePrerequisites($original, $newAssignment);

            return $newAssignment->fresh(['questions', 'prerequisites', 'lesson', 'creator']);
        });
    }

    private function prepareAssignmentDataForDuplication(Assignment $original, ?array $overrides): array
    {
        return [
            'assignable_type' => $original->assignable_type,
            'assignable_id' => $original->assignable_id,
            'created_by' => $overrides['created_by'] ?? $original->created_by,
            'title' => $overrides['title'] ?? $original->title.' (Copy)',
            'description' => $overrides['description'] ?? $original->description,
            'submission_type' => $original->submission_type?->value ?? $original->getRawOriginal('submission_type'),
            'max_score' => $overrides['max_score'] ?? $original->max_score,
            'available_from' => $overrides['available_from'] ?? $original->available_from,
            'deadline_at' => $overrides['deadline_at'] ?? $original->deadline_at,
            'tolerance_minutes' => $overrides['tolerance_minutes'] ?? $original->tolerance_minutes,
            'max_attempts' => $overrides['max_attempts'] ?? $original->max_attempts,
            'cooldown_minutes' => $overrides['cooldown_minutes'] ?? $original->cooldown_minutes,
            'retake_enabled' => $overrides['retake_enabled'] ?? $original->retake_enabled,
            'review_mode' => $overrides['review_mode'] ?? ($original->review_mode?->value ?? $original->getRawOriginal('review_mode')),
            'randomization_type' => $overrides['randomization_type'] ?? ($original->randomization_type?->value ?? $original->getRawOriginal('randomization_type')),
            'question_bank_count' => $overrides['question_bank_count'] ?? $original->question_bank_count,
            'status' => $overrides['status'] ?? AssignmentStatus::Draft->value, 
            'allow_resubmit' => $overrides['allow_resubmit'] ?? $original->allow_resubmit,
            'late_penalty_percent' => $overrides['late_penalty_percent'] ?? $original->late_penalty_percent,
            'time_limit_minutes' => $original->time_limit_minutes,
        ];
    }

    private function duplicateQuestions(Assignment $original, Assignment $newAssignment): void
    {
        foreach ($original->questions as $question) {
            $newAssignment->questions()->create([
                'type' => $question->type?->value ?? $question->getRawOriginal('type'),
                'content' => $question->content,
                'options' => $question->options,
                'answer_key' => $question->answer_key,
                'weight' => $question->weight,
                'order' => $question->order, 
            ]);
            // Note: Media duplication for questions might be needed if they have embedded media, 
            // but for now relying on basic duplication logic.
        }
    }

    private function duplicatePrerequisites(Assignment $original, Assignment $newAssignment): void
    {
        // Prerequisites are many-to-many. 
        // Logic: Should the new assignment have the same prerequisites as the old one?
        // Usually yes.
        $prereqIds = $original->prerequisites->pluck('id')->toArray();
        if (!empty($prereqIds)) {
            $newAssignment->prerequisites()->attach($prereqIds);
        }
    }
}
