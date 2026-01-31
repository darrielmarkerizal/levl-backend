<?php

declare(strict_types=1);

namespace Modules\Enrollments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_key' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'enrollment_key' => __('validation.attributes.enrollment_key'),
        ];
    }

    public function messages(): array
    {
        return [
            'enrollment_key.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.enrollment_key'),
                'max' => 100,
            ]),
        ];
    }
}
