<?php

namespace Modules\Content\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => 'required|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'Waktu publikasi wajib diisi.',
            'scheduled_at.after' => 'Waktu publikasi harus di masa depan.',
        ];
    }
}
