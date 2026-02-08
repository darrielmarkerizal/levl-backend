<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Enrollments\Models\Enrollment;
use Modules\Schemes\Models\Course;

class MentionUserSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $course = $this->route('course');

        if (! $user || ! $course instanceof Course) {
            return false;
        }

        if ($user->hasRole(['Admin', 'Superadmin'])) {
            return true;
        }

        if ($user->hasRole('Instructor')) {
            if ((int) $course->instructor_id === $user->id) {
                return true;
            }

            return Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();
        }

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'search' => 'required|string|min:1|max:50',
            'limit' => 'sometimes|integer|min:1|max:20',
        ];
    }
}
