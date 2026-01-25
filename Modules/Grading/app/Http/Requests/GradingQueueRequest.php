<?php

declare(strict_types=1);

namespace Modules\Grading\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradingQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id' => ['nullable', 'integer', 'exists:assignments,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment_id' => __('validation.attributes.assignment'),
            'user_id' => __('validation.attributes.student'),
            'date_from' => __('validation.attributes.date_from'),
            'date_to' => __('validation.attributes.date_to'),
        ];
    }
}
