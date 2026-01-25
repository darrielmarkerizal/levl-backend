<?php

declare(strict_types=1);

namespace Modules\Grading\Contracts\Services;

use Illuminate\Support\Collection;
use Modules\Grading\Models\Grade;

interface GradingServiceInterface
{
    public function autoGrade(int $submissionId): void;

    public function manualGrade(int $submissionId, array $grades, ?string $feedback = null): Grade;

    public function saveDraftGrade(int $submissionId, array $partialGrades): void;

    public function getDraftGrade(int $submissionId): ?array;

    public function calculateScore(int $submissionId): float;

    public function recalculateAfterAnswerKeyChange(int $questionId): void;

    public function overrideGrade(int $submissionId, float $score, string $reason): void;

    public function getGradingQueue(array $filters = []): Collection;

    public function returnToQueue(int $submissionId): void;

    public function validateGradingComplete(int $submissionId): bool;

    public function releaseGrade(int $submissionId): void;

    public function validateBulkReleaseGrades(array $submissionIds): array;

    public function bulkReleaseGrades(array $submissionIds): array;

    public function validateBulkApplyFeedback(array $submissionIds): array;

    public function bulkApplyFeedback(array $submissionIds, string $feedback): array;

    public function recalculateCourseGrade(int $studentId, int $courseId): ?float;

    public function calculateCourseGrade(int $studentId, int $courseId): float;
}
