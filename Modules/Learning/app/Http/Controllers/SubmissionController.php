<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Http\Requests\GradeSubmissionRequest;
use Modules\Learning\Http\Requests\SearchSubmissionsRequest;
use Modules\Learning\Http\Requests\StartSubmissionRequest;
use Modules\Learning\Http\Requests\StoreSubmissionRequest;
use Modules\Learning\Http\Requests\SubmitAnswersRequest;
use Modules\Learning\Http\Requests\UpdateSubmissionRequest;
use Modules\Learning\Http\Resources\AnswerResource;
use Modules\Learning\Http\Resources\SubmissionResource;
use Modules\Learning\Http\Resources\SubmissionConfirmationResource;
use Modules\Learning\Http\Resources\SubmissionListResource;
use Modules\Learning\Http\Resources\SubmissionDetailResource;
use Modules\Learning\Http\Resources\AnswerDetailResource;
use Modules\Learning\Http\Requests\SaveAnswerRequest;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Submission;
use Modules\Learning\Http\Resources\QuestionResource;

class SubmissionController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly SubmissionServiceInterface $service,
        private readonly ReviewModeServiceInterface $reviewModeService
    ) {}

    public function index(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);

        $user = auth('api')->user();
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->listForAssignment($assignment, $user, $request->all());

        $paginator->getCollection()->transform(fn($item) => new SubmissionResource($item));

        return $this->paginateResponse($paginator, 'messages.submissions.list_retrieved');
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('createForAssignment', [Submission::class, $assignment]);

        $user = auth('api')->user();
        $validated = $request->validated();

        $submission = $this->service->create($assignment, $user->id, $validated);

        return $this->created(
            SubmissionResource::make($submission),
            __('messages.submissions.created')
        );
    }



    public function update(UpdateSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('update', $submission);

        $validated = $request->validated();
        $updated = $this->service->update($submission, $validated);

        return $this->success(
            SubmissionResource::make($updated),
            __('messages.submissions.updated')
        );
    }

    public function start(StartSubmissionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('createForAssignment', [Submission::class, $assignment]);

        $user = auth('api')->user();
        $submission = $this->service->startSubmission($assignment->id, $user->id);

        return $this->created(
            SubmissionResource::make($submission),
            __('messages.submissions.started')
        );
    }

    public function submit(SubmitAnswersRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('submit', $submission);

        $validated = $request->validated();
        $submitted = $this->service->submitAnswers($submission->id, $validated['answers'] ?? []);

        return $this->success(
            SubmissionConfirmationResource::make($submitted),
            __('messages.submissions.submitted')
        );
    }

    public function listQuestions(Request $request, Submission $submission): JsonResponse
    {
        $this->authorize('accessQuestions', $submission);

        $page = $request->query('page');
        $perPage = $request->query('per_page');

        if ($page || $perPage) {
            $paginator = $this->service->getSubmissionQuestionsPaginated(
                $submission,
                (int) ($perPage ?? 1)
            );
            
            $paginator->getCollection()->transform(fn ($item) => new QuestionResource($item));
            
            return $this->paginateResponse($paginator);
        }

        $questions = $this->service->getSubmissionQuestions($submission);

        return $this->success(QuestionResource::collection($questions));
    }

    public function saveAnswer(SaveAnswerRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('saveAnswer', $submission);

        $validated = $request->validated();
        $answer = $this->service->saveAnswer($submission, (int) $validated['question_id'], $validated['answer'] ?? null);

        return $this->success(
            AnswerResource::make($answer),
            __('messages.answers.saved')
        );
    }

    public function grade(GradeSubmissionRequest $request, Submission $submission): JsonResponse
    {
        $this->authorize('grade', $submission);

        $user = auth('api')->user();
        $validated = $request->validated();

        $graded = $this->service->grade(
            $submission,
            $validated['score'],
            $user->id,
            $validated['feedback'] ?? null
        );

        return $this->success(
            SubmissionResource::make($graded),
            __('messages.submissions.graded')
        );
    }

    public function checkDeadline(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $allowed = $this->service->checkDeadlineWithOverride($assignment, $user->id);

        return $this->success([
            'allowed' => $allowed,
            'deadline' => $assignment->deadline_at?->toIso8601String(),
            'tolerance_minutes' => $assignment->tolerance_minutes,
        ]);
    }

    public function checkAttempts(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $result = $this->service->checkAttemptLimitsWithOverride($assignment, $user->id);

        return $this->success($result);
    }

    public function mySubmissions(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $submissions = $this->service->getSubmissionsWithHighestMarked($assignment->id, $user->id);

        return $this->success(SubmissionListResource::collection($submissions));
    }

    public function showForAssignment(Request $request, Assignment $assignment, Submission $submission): JsonResponse
    {
        $this->authorize('view', $submission);

        if ($submission->assignment_id !== $assignment->id) {
            return $this->error(__('messages.submissions.not_found'), [], 404);
        }

        $user = auth('api')->user();
        
        if ($user->hasRole('Student') && $submission->user_id !== $user->id) {
             return $this->error(__('messages.common.unauthorized'), [], 403);
        }

        $submission = \Spatie\QueryBuilder\QueryBuilder::for(
            Submission::where('id', $submission->id)
        )
            ->allowedIncludes([
                'assignment',
                'answers',
                'answers.question',
                'user',
                'enrollment',
                'grade',
                'files'
            ])
            ->first();

        // Ensure default relations are loaded if not requested
        $submission->loadMissing(['assignment', 'answers.question']);

        $visibility = $this->reviewModeService->getVisibilityStatus($submission, $user?->id);

        return $this->success(
            (new SubmissionDetailResource($submission))->withVisibility($visibility)
        );
    }

    public function highestSubmission(Request $request, Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();
        $submission = $this->service->getHighestScoreSubmission($assignment->id, $user->id);

        if (!$submission) {
            return $this->error(__('messages.submissions.not_found'), [], 404);
        }

        return $this->success(SubmissionResource::make($submission));
    }

    public function search(SearchSubmissionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Submission::class);

        $result = $this->service->searchSubmissions(
            $request->validated('query', ''),
            $request->validated('filters', []),
            ['per_page' => (int) $request->validated('per_page', 15), 'page' => (int) $request->validated('page', 1)]
        );

        $result['data']->transform(fn($item) => new SubmissionResource($item));

        return $this->success([
            'data' => $result['data'],
            'meta' => ['total' => $result['total'], 'per_page' => $result['per_page'], 'current_page' => $result['current_page'], 'last_page' => $result['last_page']],
        ]);
    }
}
