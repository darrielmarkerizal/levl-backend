<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'sometimes|string|max:100',
            'email' => "sometimes|email|max:191|unique:users,email,{$userId}",
            'phone' => 'sometimes|nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'bio' => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Name cannot exceed 100 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken.',
            'phone.regex' => 'Please provide a valid phone number.',
            'bio.max' => 'Bio cannot exceed 1000 characters.',
        ];
    }
}
