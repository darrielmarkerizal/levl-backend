<?php

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Schemes\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class CourseRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->route('course') ? (int) $this->route('course') : 0;

        return $this->rulesCourse($courseId);
    }

    public function messages(): array
    {
        return $this->messagesCourse();
    }

    protected function prepareForValidation(): void
    {
        $fields = ['tags', 'outcomes', 'prereq', 'course_admins'];
        $payload = [];
        foreach ($fields as $field) {
            $val = $this->input($field);
            if (is_string($val)) {
                $decoded = $this->decodeJsonArrayString($val);
                if (is_array($decoded)) {
                    $payload[$field] = $decoded;
                }
            }
        }
        if (! empty($payload)) {
            $this->merge($payload);
        }
    }

    private function decodeJsonArrayString(string $value): ?array
    {
        $trim = trim($value);
        if ($trim === '') {
            return null;
        }

        if ($trim[0] === '[') {
            $decoded = json_decode($trim, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $urldec = urldecode($trim);
        if ($urldec !== $trim && strlen($urldec) > 0 && $urldec[0] === '[') {
            $decoded = json_decode($urldec, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if (array_key_exists('tags', $data)) {
            $data['tags_json'] = $data['tags'];
            unset($data['tags']);
        }
        if (array_key_exists('outcomes', $data)) {
            $data['outcomes_json'] = $data['outcomes'];
            unset($data['outcomes']);
        }
        if (array_key_exists('prereq', $data)) {
            $data['prereq_json'] = $data['prereq'];
            unset($data['prereq']);
        }

        return $data;
    }
}
