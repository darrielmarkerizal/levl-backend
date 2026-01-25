<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Grading\Contracts\Services\GradingServiceInterface;
use Modules\Grading\Http\Requests\BulkFeedbackRequest;
use Modules\Grading\Http\Requests\BulkReleaseGradesRequest;
use Modules\Grading\Http\Requests\GradingQueueRequest;
use Modules\Grading\Http\Requests\ManualGradeRequest;
use Modules\Grading\Http\Requests\OverrideGradeRequest;
use Modules\Grading\Http\Requests\SaveDraftGradeRequest;
use Modules\Grading\Http\Resources\DraftGradeResource;
use Modules\Grading\Http\Resources\GradeResource;
use Modules\Grading\Http\Resources\GradingQueueItemResource;
use Modules\Grading\Jobs\BulkApplyFeedbackJob;
use Modules\Grading\Jobs\BulkReleaseGradesJob;
use Modules\Learning\Models\Submission;

class GradingController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly GradingServiceInterface $gradingService
    ) {}

    public function manualGrade(ManualGradeRequest $request, Submission $submission): JsonResponse
    {
        try {
            $validated = $request->validated();
            $grades = collect($validated['grades'])->keyBy('question_id')->toArray();

            $grade = $this->gradingService->manualGrade(
                $submission->id,
                $grades,
                $validated['feedback'] ?? null
            );

            $grade->load(['submission', 'user', 'grader']);

            return $this->success(
                GradeResource::make($grade),
                __('messages.grading.manual_graded')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function queue(GradingQueueRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = array_filter([
            'assignment_id' => $validated['assignment_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ], fn ($value) => $value !== null);

        $queue = $this->gradingService->getGradingQueue($filters);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $items = $queue->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->success(
            GradingQueueItemResource::collection($items),
            __('messages.grading.queue_fetched')
        );
    }

    public function returnToQueue(Submission $submission): JsonResponse
    {
        try {
            $this->gradingService->returnToQueue($submission->id);
            $submission->refresh();

            return $this->success(
                ['submission_id' => $submission->id, 'state' => $submission->state?->value],
                __('messages.grading.returned_to_queue')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function saveDraftGrade(SaveDraftGradeRequest $request, Submission $submission): JsonResponse
    {
        try {
            $validated = $request->validated();
            $grades = collect($validated['grades'])->keyBy('question_id')->toArray();

            $this->gradingService->saveDraftGrade($submission->id, $grades);

            return $this->success(
                ['submission_id' => $submission->id],
                __('messages.grading.draft_saved')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function getDraftGrade(Submission $submission): JsonResponse
    {
        $draftGrade = $this->gradingService->getDraftGrade($submission->id);

        if ($draftGrade === null) {
            return $this->success(null);
        }

        return $this->success(
            DraftGradeResource::make($draftGrade)
        );
    }

    public function overrideGrade(OverrideGradeRequest $request, Submission $submission): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->gradingService->overrideGrade(
                $submission->id,
                (float) $validated['score'],
                $validated['reason']
            );

            $submission->refresh();
            $submission->load('grade');

            return $this->success(
                [
                    'submission_id' => $submission->id,
                    'score' => $submission->score,
                    'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
                ],
                __('messages.grading.grade_overridden')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function releaseGrade(Submission $submission): JsonResponse
    {
        try {
            $this->gradingService->releaseGrade($submission->id);

            $submission->refresh();
            $submission->load('grade');

            return $this->success(
                [
                    'submission_id' => $submission->id,
                    'state' => $submission->state?->value,
                    'grade' => $submission->grade ? GradeResource::make($submission->grade) : null,
                ],
                __('messages.grading.grade_released')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function bulkReleaseGrades(BulkReleaseGradesRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $submissionIds = $validated['submission_ids'];
            $async = $validated['async'] ?? false;

            if ($async) {
                $validation = $this->gradingService->validateBulkReleaseGrades($submissionIds);

                if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                    return $this->error(
                        'Bulk release validation failed: '.implode('; ', $validation['errors']),
                        ['errors' => $validation['errors']],
                        422
                    );
                }

                BulkReleaseGradesJob::dispatch($submissionIds, auth('api')->user()->id);

                return $this->success(
                    [
                        'message' => 'Bulk grade release job has been queued for processing.',
                        'submission_count' => count($submissionIds),
                        'async' => true,
                    ],
                    __('messages.grading.bulk_release_queued')
                );
            }

            $result = $this->gradingService->bulkReleaseGrades($submissionIds);

            return $this->success(
                [
                    'success_count' => $result['success'],
                    'failed_count' => $result['failed'],
                    'released_submissions' => $result['submissions']->pluck('id'),
                    'errors' => $result['errors'],
                    'async' => false,
                ],
                __('messages.grading.bulk_released')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function bulkApplyFeedback(BulkFeedbackRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $submissionIds = $validated['submission_ids'];
            $feedback = $validated['feedback'];
            $async = $validated['async'] ?? false;

            if ($async) {
                $validation = $this->gradingService->validateBulkApplyFeedback($submissionIds);

                if (! $validation['valid'] && count($validation['errors']) === count($submissionIds)) {
                    return $this->error(
                        'Bulk feedback validation failed: '.implode('; ', $validation['errors']),
                        ['errors' => $validation['errors']],
                        422
                    );
                }

                BulkApplyFeedbackJob::dispatch($submissionIds, $feedback, auth('api')->user()->id);

                return $this->success(
                    [
                        'message' => 'Bulk feedback application job has been queued for processing.',
                        'submission_count' => count($submissionIds),
                        'async' => true,
                    ],
                    __('messages.grading.bulk_feedback_queued')
                );
            }

            $result = $this->gradingService->bulkApplyFeedback($submissionIds, $feedback);

            return $this->success(
                [
                    'success_count' => $result['success'],
                    'failed_count' => $result['failed'],
                    'updated_submissions' => $result['submissions']->pluck('id'),
                    'errors' => $result['errors'],
                    'async' => false,
                ],
                __('messages.grading.bulk_feedback_applied')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function gradingStatus(Submission $submission): JsonResponse
    {
        $isComplete = $this->gradingService->validateGradingComplete($submission->id);

        $submission->load(['answers.question', 'grade']);

        $gradedCount = $submission->answers->filter(fn ($a) => $a->score !== null)->count();
        $totalCount = $submission->answers->count();

        return $this->success(
            [
                'submission_id' => $submission->id,
                'is_complete' => $isComplete,
                'graded_questions' => $gradedCount,
                'total_questions' => $totalCount,
                'can_finalize' => $isComplete,
                'can_release' => $isComplete && $submission->grade && ! $submission->grade->is_draft,
            ]
        );
    }
}
