<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'grades.*.score' => ['required', 'numeric', 'min:0'],
            'grades.*.feedback' => ['nullable', 'string'],
            'feedback' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'grades' => __('validation.attributes.grades'),
            'grades.*.question_id' => __('validation.attributes.question_id'),
            'grades.*.score' => __('validation.attributes.score'),
            'grades.*.feedback' => __('validation.attributes.feedback'),
            'feedback' => __('validation.attributes.overall_feedback'),
        ];
    }

    public function messages(): array
    {
        return [
            'grades.required' => 'At least one grade must be provided.',
            'grades.*.score.min' => 'Score must be a non-negative number.',
        ];
    }
}
