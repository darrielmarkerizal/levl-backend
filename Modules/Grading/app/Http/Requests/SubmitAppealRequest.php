<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'documents' => ['sometimes', 'array'],
            'documents.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => __('validation.attributes.reason'),
            'documents' => __('validation.attributes.documents'),
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for the appeal.',
            'reason.min' => 'The reason must be at least 10 characters.',
            'documents.*.max' => 'Each document must not exceed 10MB.',
        ];
    }
}
