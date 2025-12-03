<?php

namespace Modules\Assessments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAssessmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'enrollment_id.exists' => 'The selected enrollment does not exist.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
            'payment_amount.min' => 'The payment amount must be at least 0.',
        ];
    }
}
