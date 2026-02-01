<?php

declare(strict_types=1);

namespace Modules\Learning\Services\Support;

use Illuminate\Support\Collection;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\DTOs\PrerequisiteCheckResult;
use Modules\Learning\Exceptions\AssignmentException;
use Modules\Learning\Models\Assignment;
use Modules\Schemes\Contracts\Services\LessonServiceInterface;

class AssignmentPrerequisiteProcessor
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $repository,
        private readonly SubmissionRepositoryInterface $submissionRepository,
        private readonly ?LessonServiceInterface $lessonService = null
    ) {}

    public function checkPrerequisites(
        int $assignmentId,
        int $studentId,
        AssignmentOverrideProcessor $overrideProcessor
    ): PrerequisiteCheckResult {
        $assignment = $this->repository->findWithPrerequisites($assignmentId);

        if (!$assignment) {
            throw AssignmentException::notFound();
        }

        if ($assignment->prerequisites->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        if ($overrideProcessor->hasPrerequisiteOverride($assignmentId, $studentId)) {
            return PrerequisiteCheckResult::pass();
        }

        $prerequisitesToCheck = $this->getPrerequisitesForScope($assignment);

        if ($prerequisitesToCheck->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        $incompletePrerequisites = $this->getIncompletePrerequisites(
            $prerequisitesToCheck,
            $studentId,
            $assignmentId,
            $overrideProcessor
        );

        if ($incompletePrerequisites->isEmpty()) {
            return PrerequisiteCheckResult::pass();
        }

        return PrerequisiteCheckResult::fail($incompletePrerequisites);
    }

    public function addPrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        if ($assignmentId === $prerequisiteId) {
            throw new \InvalidArgumentException(__('messages.assignments.cannot_be_own_prerequisite'));
        }

        if ($this->hasCircularDependency($assignmentId, $prerequisiteId)) {
            throw new \InvalidArgumentException(__('messages.assignments.circular_dependency'));
        }

        $this->repository->attachPrerequisite($assignmentId, $prerequisiteId);
    }

    public function removePrerequisite(int $assignmentId, int $prerequisiteId): void
    {
        $this->repository->detachPrerequisite($assignmentId, $prerequisiteId);
    }

    public function hasCircularDependency(int $assignmentId, int $prerequisiteId): bool
    {
        $visited = [];

        return $this->detectCycle($prerequisiteId, $assignmentId, $visited);
    }

    private function detectCycle(int $currentId, int $targetId, array &$visited): bool
    {
        if ($currentId === $targetId) {
            return true;
        }

        if (in_array($currentId, $visited, true)) {
            return false;
        }

        $visited[] = $currentId;

        $assignment = $this->repository->findWithPrerequisites($currentId);

        if (! $assignment) {
            return false;
        }

        foreach ($assignment->prerequisites as $prereq) {
            if ($this->detectCycle($prereq->id, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }

    private function getPrerequisitesForScope(Assignment $assignment): Collection
    {
        $prerequisites = $assignment->prerequisites;
        $scopeType = $assignment->scope_type; // Ensure this attribute exists or is handled

        return match ($scopeType) {
            'lesson' => $this->filterPrerequisitesByLesson($prerequisites, $assignment),
            'unit' => $this->filterPrerequisitesByUnit($prerequisites, $assignment),
            'course' => $prerequisites,
            default => $prerequisites,
        };
    }

    private function filterPrerequisitesByLesson(Collection $prerequisites, Assignment $assignment): Collection
    {
        $lessonId = $assignment->assignable_id ?? $assignment->lesson_id;

        return $prerequisites->filter(function ($prereq) use ($lessonId) {
            // Adjust based on actual model attributes if lesson_id accessor logic is complex
            $prereqLessonId = $prereq->assignable_id ?? $prereq->lesson_id;

            return $prereqLessonId === $lessonId;
        });
    }

    private function filterPrerequisitesByUnit(Collection $prerequisites, Assignment $assignment): Collection
    {
        $unitId = $assignment->assignable_id;

        $lessonIds = [];
        if ($this->lessonService !== null) {
            $lessonIds = $this->lessonService->getRepository()
                ->query()
                ->where('unit_id', $unitId)
                ->pluck('id')
                ->toArray();
        }

        return $prerequisites->filter(function ($prereq) use ($unitId, $lessonIds) {
            if ($prereq->assignable_type === \Modules\Schemes\Models\Unit::class
                && $prereq->assignable_id === $unitId) {
                return true;
            }

            $prereqLessonId = $prereq->assignable_id ?? $prereq->lesson_id;

            return in_array($prereqLessonId, $lessonIds, true);
        });
    }

    private function getIncompletePrerequisites(
        Collection $prerequisites,
        int $studentId,
        int $assignmentId,
        AssignmentOverrideProcessor $overrideProcessor
    ): Collection {
        $bypassedIds = $overrideProcessor->getBypassedPrerequisiteIds($assignmentId, $studentId);

        return $prerequisites->filter(function ($prereq) use ($studentId, $bypassedIds) {
            if (in_array($prereq->id, $bypassedIds, true)) {
                return false;
            }

            return ! $this->hasCompletedAssignment($prereq->id, $studentId);
        });
    }

    private function hasCompletedAssignment(int $assignmentId, int $studentId): bool
    {
        return $this->submissionRepository->hasCompletedAssignment($assignmentId, $studentId);
    }
}
