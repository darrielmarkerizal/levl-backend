<?php

namespace Modules\Assessments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Modules\Assessments\Models\Question;
use Modules\Assessments\Models\QuestionOption;
use Modules\Assessments\Services\QuestionOptionService;

class QuestionOptionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly QuestionOptionService $service) {}

    /**
     * Create new option for question
     */
    public function store(Request $request, Question $question)
    {
        $this->authorize('update', $question->exercise);

        $validated = $request->validate([
            'option_text' => 'required|string',
            'is_correct' => 'boolean',
            'order' => 'integer|min:1',
        ]);

        $option = $this->service->create($question, $validated);

        return $this->created(['option' => $option], 'Opsi pertanyaan berhasil dibuat');
    }

    /**
     * Update option
     */
    public function update(Request $request, QuestionOption $option)
    {
        $this->authorize('update', $option->question->exercise);

        $validated = $request->validate([
            'option_text' => 'sometimes|string',
            'is_correct' => 'boolean',
            'order' => 'integer|min:1',
        ]);

        $option = $this->service->update($option, $validated);

        return $this->success(['option' => $option], 'Opsi pertanyaan berhasil diperbarui');
    }

    /**
     * Delete option
     */
    public function destroy(QuestionOption $option)
    {
        $this->authorize('update', $option->question->exercise);

        $this->service->delete($option);

        return $this->noContent();
    }
}
