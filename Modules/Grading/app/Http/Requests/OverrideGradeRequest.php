<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OverrideGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'score' => __('validation.attributes.score'),
            'reason' => __('validation.attributes.reason'),
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for grade override.',
            'reason.min' => 'The reason must be at least 10 characters.',
        ];
    }
}
