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
        $paginator = $this->assignmentService->listForIndex($course, $request->all());
        $paginator->getCollection()->transform(fn($item) => new \Modules\Learning\Http\Resources\AssignmentIndexResource($item));

        return $this->paginateResponse($paginator, 'messages.assignments.list_retrieved');
    }

    public function indexIncomplete(Request $request, \Modules\Schemes\Models\Course $course): JsonResponse
    {
        $paginator = $this->assignmentService->listIncomplete($course, auth('api')->id(), $request->all());
        $paginator->getCollection()->transform(fn($item) => new AssignmentResource($item));

        return $this->paginateResponse($paginator, 'messages.assignments.incomplete_list_retrieved');
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $course = $this->assignmentService->resolveCourseFromScopeOrFail($request->getResolvedScope());
        $this->authorize('create', [Assignment::class, $course]);
        
        $assignment = $this->assignmentService->create($request->validated(), auth('api')->id());

        return $this->created(AssignmentResource::make($assignment), __('messages.assignments.created'));
    }

    public function show(Assignment $assignment): JsonResponse
    {
        return $this->success(AssignmentResource::make($this->assignmentService->getWithRelations($assignment)));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $updated = $this->assignmentService->update($assignment, $request->validated());

        return $this->success(AssignmentResource::make($updated), __('messages.assignments.updated'));
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $this->assignmentService->delete($assignment);

        return $this->success([], __('messages.assignments.deleted'));
    }

    public function publish(Assignment $assignment): JsonResponse
    {
        $updated = $this->assignmentService->publish($assignment);

        return $this->success(AssignmentResource::make($updated), __('messages.assignments.published'));
    }

    public function unpublish(Assignment $assignment): JsonResponse
    {
        $updated = $this->assignmentService->unpublish($assignment);

        return $this->success(AssignmentResource::make($updated), __('messages.assignments.unpublished'));
    }

    public function archive(Assignment $assignment): JsonResponse
    {
        $archived = $this->assignmentService->archive($assignment);

        return $this->success(AssignmentResource::make($archived), __('messages.assignments.archived'));
    }

    public function listQuestions(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('listQuestions', $assignment);
        $questions = $this->questionService->getQuestionsByAssignment($assignment->id, auth('api')->user(), $request->all());

        return $this->success(QuestionResource::collection($questions));
    }

    public function showQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('view', $assignment);
        // Note: Relation check moved to logic or kept merely for 404. Service could handle "Question in Assignment" check.
        // For strict thinness, we assume route model binding + potential check inside service or just this check.
        // But 5 line limit:
        if ($question->assignment_id !== $assignment->id) return $this->error(__('messages.questions.not_found'), [], 404);

        return $this->success(QuestionResource::make($question));
    }

    public function addQuestion(StoreQuestionRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);
        $question = $this->questionService->createQuestion($assignment->id, $request->validated());

        return $this->created(QuestionResource::make($question), __('messages.questions.created'));
    }

    public function updateQuestion(UpdateQuestionRequest $request, Assignment $assignment, Question $question): JsonResponse 
    {
        $this->authorize('update', $assignment);
        $updated = $this->questionService->updateQuestion($question->id, $request->validated(), $assignment->id);

        return $this->success(QuestionResource::make($updated), __('messages.questions.updated'));
    }

    public function deleteQuestion(Assignment $assignment, Question $question): JsonResponse
    {
        $this->authorize('update', $assignment);
        $this->questionService->deleteQuestion($question->id, $assignment->id);

        return $this->success([], __('messages.questions.deleted'));
    }

    public function checkPrerequisites(Assignment $assignment): JsonResponse
    {
        $result = $this->assignmentService->checkPrerequisites($assignment->id, auth('api')->id());

        return $this->success($result->toArray());
    }

    public function grantOverride(GrantOverrideRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('grantOverride', $assignment);
        // We unpack args to keep service signature clean & explicit
        $override = $this->assignmentService->grantOverride($assignment->id, (int) $request->validated('student_id'),
            (string) $request->validated('type'), (string) $request->validated('reason'), $request->validated('value', []), auth('api')->id());

        return $this->created(OverrideResource::make($override), __('messages.overrides.granted'));
    }

    public function listOverrides(Assignment $assignment): JsonResponse
    {
        $this->authorize('viewOverrides', $assignment);
        return $this->success(OverrideResource::collection($this->assignmentService->getOverridesForAssignment($assignment->id)));
    }

    public function duplicate(DuplicateAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('duplicate', $assignment);
        $duplicated = $this->assignmentService->duplicateAssignment($assignment->id, auth('api')->id(), $request->validated());

        return $this->created(AssignmentResource::make($duplicated), __('messages.assignments.duplicated'));
    }

    public function reorderQuestions(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);
        // move validation to FormRequest generally, but for simple array id:
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids']; 
        $this->questionService->reorderQuestions($assignment->id, $ids);

        return $this->success([], __('messages.questions.reordered'));
    }
}
