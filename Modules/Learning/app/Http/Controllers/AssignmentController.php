<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Learning\Contracts\Services\AssignmentServiceInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Http\Requests\DuplicateAssignmentRequest;
use Modules\Learning\Http\Requests\GrantOverrideRequest;
use Modules\Learning\Http\Requests\StoreAssignmentRequest;
use Modules\Learning\Http\Requests\StoreQuestionRequest;
use Modules\Learning\Http\Requests\UpdateAssignmentRequest;
use Modules\Learning\Http\Requests\UpdateQuestionRequest;
use Modules\Learning\Http\Resources\AssignmentResource;
use Modules\Learning\Http\Resources\OverrideResource;
use Modules\Learning\Http\Resources\QuestionResource;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Question;

class AssignmentController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    public function __construct(
        private readonly AssignmentServiceInterface $assignmentService,
        private readonly QuestionServiceInterface $questionService
    ) {}

    public function index(Request $request, \Modules\Schemes\Models\Course $course): JsonResponse
    {
        try {
            $paginator = $this->assignmentService->list($course, $request->all());

            $paginator->getCollection()->transform(fn($item) => new AssignmentResource($item));

            return $this->paginateResponse($paginator, 'messages.assignments.list_retrieved');
        } catch (\Modules\Learning\Exceptions\AssignmentException $e) {
            return $this->error($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($e->getMessage(), [], 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    public function indexIncomplete(Request $request, \Modules\Schemes\Models\Course $course): JsonResponse
    {
        try {
            $user = auth('api')->user();
            $paginator = $this->assignmentService->listIncomplete($course, $user->id, $request->all());

            $paginator->getCollection()->transform(fn($item) => new AssignmentResource($item));

            return $this->paginateResponse($paginator, 'messages.assignments.incomplete_list_retrieved');
        } catch (\Modules\Learning\Exceptions\AssignmentException $e) {
            return $this->error($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error($e->getMessage(), [], 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 400);
        }
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();
        
        $scope = $request->getResolvedScope();
        
        if (!$scope) {
            return $this->error(__('messages.assignments.invalid_scope'), 422);
        }

        $validated['assignable_type'] = $scope['assignable_type'];
        $validated['assignable_id'] = $scope['assignable_id'];

        $course = $this->assignmentService->resolveCourseFromScope(
            $validated['assignable_type'],
            $validated['assignable_id']
        );

        if (!$course) {
            return $this->error(__('messages.assignments.invalid_scope'), 422);
        }

        $this->authorize('create', [Assignment::class, $course]);

        $assignment = $this->assignmentService->create($validated, $user->id);

        return $this->created(
            AssignmentResource::make($assignment),
            __('messages.assignments.created')
        );
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->getWithRelations($assignment);

        return $this->success(AssignmentResource::make($assignment));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $validated = $request->validated();

        $updated = $this->assignmentService->update($assignment, $validated);

        return $this->success(
            AssignmentResource::make($updated),
            __('messages.assignments.updated')
        );
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->assignmentService->delete($assignment);

        return $this->success([], __('messages.assignments.deleted'));
    }

    public function publish(Assignment $assignment): JsonResponse
    {
        $updated = $this->assignmentService->publish($assignment);

        return $this->success(
            AssignmentResource::make($updated),
            __('messages.assignments.published')
        );
    }

    public function unpublish(Assignment $assignment): JsonResponse
    {
        $updated = $this->assignmentService->unpublish($assignment);

        return $this->success(
            AssignmentResource::make($updated),
            __('messages.assignments.unpublished')
        );
    }

    public function archive(Assignment $assignment): JsonResponse
    {
        $archived = $this->assignmentService->archive($assignment);

        return $this->success(
            AssignmentResource::make($archived),
            __('messages.assignments.archived')
        );
    }

    public function listQuestions(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('listQuestions', $assignment);

        $user = auth('api')->user();

        $questions = $this->questionService->getQuestionsByAssignment($assignment->id, $user, $request->all());

        return $this->success(QuestionResource::collection($questions));
    }

    public function showQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('view', $assignment);

        if ($question->assignment_id !== $assignment->id) {
            return $this->error(__('messages.questions.not_found'), [], 404);
        }

        return $this->success(QuestionResource::make($question));
    }

    public function addQuestion(StoreQuestionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validated();

        $question = $this->questionService->createQuestion($assignment->id, $validated);

        return $this->created(
            QuestionResource::make($question),
            __('messages.questions.created')
        );
    }

    public function updateQuestion(
        UpdateQuestionRequest $request,
        Assignment $assignment,
        Question $question
    ): JsonResponse {
        $this->authorize('update', $assignment);

        $validated = $request->validated();

        $updated = $this->questionService->updateQuestion($question->id, $validated, $assignment->id);

        return $this->success(
            QuestionResource::make($updated),
            __('messages.questions.updated')
        );
    }

    public function deleteQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('update', $assignment);

        $this->questionService->deleteQuestion($question->id, $assignment->id);

        return $this->success([], __('messages.questions.deleted'));
    }

    public function checkPrerequisites(Assignment $assignment): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->assignmentService->checkPrerequisites($assignment->id, $user->id);

        return $this->success($result->toArray());
    }

    public function grantOverride(GrantOverrideRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('grantOverride', $assignment);

        $override = $this->assignmentService->grantOverride(
            assignmentId: $assignment->id,
            studentId: (int) $request->validated('student_id'),
            overrideType: (string) $request->validated('type'),
            reason: (string) $request->validated('reason'),
            value: $request->validated('value', []),
            grantorId: auth('api')->user()->id
        );

        return $this->created(OverrideResource::make($override), __('messages.overrides.granted'));
    }

    public function listOverrides(Assignment $assignment): JsonResponse
    {
        $this->authorize('viewOverrides', $assignment);

        $overrides = $this->assignmentService->getOverridesForAssignment($assignment->id);

        return $this->success(OverrideResource::collection($overrides));
    }

    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('duplicate', $assignment);

        $overrides = array_merge($request->validated(), ['created_by' => auth('api')->user()->id]);
        $duplicated = $this->assignmentService->duplicateAssignment($assignment->id, $overrides);

        return $this->created(AssignmentResource::make($duplicated), __('messages.assignments.duplicated'));
    }

    public function reorderQuestions(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'exists:assignment_questions,id']]);
        $this->questionService->reorderQuestions($assignment->id, $validated['ids']);

        return $this->success([], __('messages.questions.reordered'));
    }
}
