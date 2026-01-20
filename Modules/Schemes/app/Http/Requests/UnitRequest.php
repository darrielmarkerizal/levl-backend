<?php

declare(strict_types=1);

namespace Modules\Schemes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Http\Requests\Concerns\HasApiValidation;
use Modules\Schemes\Http\Requests\Concerns\HasSchemesRequestRules;

class UnitRequest extends FormRequest
{
    use HasApiValidation, HasSchemesRequestRules;

    protected function rulesUnit(int $courseId, int $unitId = 0): array
    {
        return [
            'code' => [
                'required', 
                'string', 
                'max:50', 
                \Illuminate\Validation\Rule::unique('units', 'code')->ignore($unitId)
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => [
                'nullable', 
                'integer', 
                'min:1', 
                \Illuminate\Validation\Rule::unique('units')->where(function ($query) use ($courseId) {
                    return $query->where('course_id', $courseId);
                })->ignore($unitId)
            ],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }

    protected function messagesUnit(): array
    {
        return [
            'code.required' => __('messages.units.code_required'),
            'code.unique' => __('messages.units.code_unique'),
            'title.required' => __('messages.units.title_required'),
            'order.unique' => __('messages.units.order_unique'),
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courseId = $this->resolveCourseId();
        $unitId = $this->resolveUnitId($courseId);

        return $this->rulesUnit($courseId, $unitId);
    }

    private function resolveCourseId(): int
    {
        $course = $this->route('course');

        if ($course instanceof \Modules\Schemes\Models\Course) {
            return $course->id;
        }

        if (is_string($course) || is_numeric($course)) {
            $q = \Modules\Schemes\Models\Course::query();
             $q->where(function($sq) use ($course) {
                $sq->where('slug', $course);
                if (is_numeric($course)) {
                    $sq->orWhere('id', $course);
                }
            });
            $id = $q->value('id');
            return $id ?? 0;
        }

        return 0;
    }

    private function resolveUnitId(int $courseId): int
    {
        $unit = $this->route('unit');
        
        if ($unit instanceof \Modules\Schemes\Models\Unit) {
            return $unit->id;
        }

        if (is_string($unit) || is_numeric($unit)) {
            $q = \Modules\Schemes\Models\Unit::query();
            
            if ($courseId) {
                $q->where('course_id', $courseId);
            }

            $q->where(function($sq) use ($unit) {
                $sq->where('slug', $unit);
                 if (is_numeric($unit)) {
                    $sq->orWhere('id', $unit);
                }
            });

            $id = $q->value('id');
            return $id ?? 0;
        }

        return 0;
    }

    public function messages(): array
    {
        return $this->messagesUnit();
    }
}
