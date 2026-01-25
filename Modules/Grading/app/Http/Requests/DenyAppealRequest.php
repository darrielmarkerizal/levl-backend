<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DenyAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => __('validation.attributes.reason'),
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required when denying an appeal.',
            'reason.min' => 'The denial reason must be at least 10 characters.',
        ];
    }
}
