<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkReleaseGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'submission_ids' => ['required', 'array', 'min:1'],
            'submission_ids.*' => ['required', 'integer', 'exists:submissions,id'],
            'async' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'submission_ids' => __('validation.attributes.submissions'),
            'submission_ids.*' => __('validation.attributes.submission'),
            'async' => __('validation.attributes.async'),
        ];
    }

    public function messages(): array
    {
        return [
            'submission_ids.required' => 'At least one submission must be selected.',
            'submission_ids.min' => 'At least one submission must be selected.',
        ];
    }
}
