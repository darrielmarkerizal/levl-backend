<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Schemes\Http\Requests\LessonBlockRequest;
use Modules\Schemes\Http\Resources\LessonBlockResource;
use Modules\Schemes\Models\Course;
use Modules\Schemes\Models\Lesson;
use Modules\Schemes\Models\LessonBlock;
use Modules\Schemes\Models\Unit;
use Modules\Schemes\Services\LessonBlockService;

class LessonBlockController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LessonBlockService $service) {}

    public function index(Request $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('view', $lesson);

        $blocks = $this->service->list($lesson->id, $request->query('filter', []));
        return $this->success(LessonBlockResource::collection($blocks));
    }

    public function store(LessonBlockRequest $request, Course $course, Unit $unit, Lesson $lesson)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('update', $lesson);

        $block = $this->service->create($lesson->id, $request->validated(), $request->file('media'));
        return $this->created(new LessonBlockResource($block), __('messages.lesson_blocks.created'));
    }

    public function show(Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('view', $block);

        return $this->success(new LessonBlockResource($block));
    }

    public function update(LessonBlockRequest $request, Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('update', $block);

        $updated = $this->service->update($lesson->id, $block->id, $request->validated(), $request->file('media'));
        return $this->success(new LessonBlockResource($updated), __('messages.lesson_blocks.updated'));
    }

    public function destroy(Course $course, Unit $unit, Lesson $lesson, LessonBlock $block)
    {
        $this->service->validateHierarchy($course->id, $unit->id, $lesson->id);
        $this->authorize('delete', $block);

        $this->service->delete($lesson->id, $block->id);
        return $this->success([], __('messages.lesson_blocks.deleted'));
    }
}
