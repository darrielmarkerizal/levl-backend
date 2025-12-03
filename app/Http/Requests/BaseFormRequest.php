<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base Form Request class with standardized error handling.
 *
 * All Form Request classes should extend this base class to ensure
 * consistent validation error responses across the application.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('messages.validation_failed'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => __('validation.required'),
            'email' => __('validation.email'),
            'unique' => __('validation.unique'),
            'string' => __('validation.string'),
            'integer' => __('validation.integer'),
            'numeric' => __('validation.numeric'),
            'array' => __('validation.array'),
            'min.string' => __('validation.min.string'),
            'max.string' => __('validation.max.string'),
            'confirmed' => __('validation.confirmed'),
            'exists' => __('validation.exists'),
            'in' => __('validation.in'),
            'date' => __('validation.date'),
            'after' => __('validation.after'),
            'before' => __('validation.before'),
            'regex' => __('validation.regex'),
        ];
    }
}
