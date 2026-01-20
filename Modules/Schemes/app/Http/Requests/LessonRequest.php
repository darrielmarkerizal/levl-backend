<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class LessonRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = $unit ? (is_object($unit) ? $unit->id : (int) $unit) : 0;
        
        $lesson = $this->route('lesson');
        $lessonId = $lesson ? (is_object($lesson) ? $lesson->id : (int) $lesson) : 0;

        return $this->rulesLesson($unitId, $lessonId);
    }

    public function messages(): array
    {
        return $this->messagesLesson();
    }

    /**
     * Note: Markdown content is NOT sanitized at input time.
     * Sanitization should be performed at render time using a proper
     * markdown parser with HTML sanitization (e.g., league/commonmark).
     * This preserves valid markdown syntax including code blocks and HTML.
     */
}
