<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Grading\Contracts\Services\AppealServiceInterface;
use Modules\Grading\Http\Requests\DenyAppealRequest;
use Modules\Grading\Http\Requests\SubmitAppealRequest;
use Modules\Grading\Http\Resources\AppealResource;
use Modules\Grading\Models\Appeal;
use Modules\Learning\Models\Submission;

class AppealController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AppealServiceInterface $appealService
    ) {}

    public function submit(SubmitAppealRequest $request, Submission $submission): JsonResponse
    {
        $user = auth('api')->user();

        if ($submission->user_id !== $user->id) {
            return $this->forbidden(__('messages.appeals.not_owner'));
        }

        try {
            $validated = $request->validated();
            $files = $request->allFiles();

            $appeal = $this->appealService->submitAppeal(
                $submission->id,
                $validated['reason'],
                $files
            );

            $appeal->load(['submission.assignment', 'student']);

            return $this->created([
                'appeal' => AppealResource::make($appeal),
            ], __('messages.appeals.submitted'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function approve(Appeal $appeal): JsonResponse
    {
        try {
            $this->appealService->approveAppeal($appeal->id, auth('api')->user()->id);

            $appeal->refresh();
            $appeal->load(['submission.assignment', 'student', 'reviewer']);

            return $this->success(
                AppealResource::make($appeal),
                __('messages.appeals.approved')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function deny(DenyAppealRequest $request, Appeal $appeal): JsonResponse
    {
        try {
            $validated = $request->validated();

            $this->appealService->denyAppeal(
                $appeal->id,
                auth('api')->user()->id,
                $validated['reason']
            );

            $appeal->refresh();
            $appeal->load(['submission.assignment', 'student', 'reviewer']);

            return $this->success(
                AppealResource::make($appeal),
                __('messages.appeals.denied')
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function pending(): JsonResponse
    {
        $appeals = $this->appealService->getPendingAppeals(auth('api')->user()->id);

        return $this->success(
            AppealResource::collection($appeals),
            __('messages.appeals.pending_fetched')
        );
    }

    public function show(Appeal $appeal): JsonResponse
    {
        $user = auth('api')->user();

        $isOwner = $appeal->student_id === $user->id;
        $isInstructor = $user->hasRole('Admin') ||
            $user->hasRole('Instructor') ||
            $user->hasRole('Superadmin');

        if (! $isOwner && ! $isInstructor) {
            return $this->forbidden(__('messages.appeals.not_authorized'));
        }

        $appeal->load(['submission.assignment', 'student', 'reviewer']);

        return $this->success(
            AppealResource::make($appeal)
        );
    }
}
