<?php

declare(strict_types=1);

namespace Modules\Learning\Http\Requests;

use Modules\Learning\Enums\QuestionType;
use Modules\Learning\Models\Question;

use Illuminate\Foundation\Http\FormRequest;

class SaveAnswerRequest extends FormRequest
{
    private ?Question $questionCache = null;

    public function authorize(): bool
    {
        return true;
    }

    private function getQuestion(): ?Question
    {
        if ($this->questionCache) {
            return $this->questionCache;
        }

        if (! $this->filled('question_id')) {
            return null;
        }

        $this->questionCache = Question::query()
            ->select(['id', 'type', 'options'])
            ->find($this->question_id);

        return $this->questionCache;
    }

    public function rules(): array
    {
        return [
            'question_id' => [
                'required',
                'integer',
                'exists:assignment_questions,id',
            ],
            'answer' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_null($value)) {
                        return;
                    }

                    $question = $this->getQuestion();

                    if (! $question) {
                        return;
                    }

                    if ($question->type === QuestionType::MultipleChoice) {
                        if (! is_int($value) && ! ctype_digit((string) $value)) {
                            $fail(__('validation.invalid_format'));

                            return;
                        }

                        $index = (int) $value;

                        if (! array_key_exists($index, $question->options ?? [])) {
                            $fail(__('validation.invalid_choice'));
                        }
                    } elseif ($question->type === QuestionType::Checkbox) {
                        if (! is_array($value)) {
                            $fail(__('validation.invalid_format'));

                            return;
                        }

                        foreach ($value as $index) {
                            if (! is_int($index) && ! ctype_digit((string) $index)) {
                                $fail(__('validation.invalid_format'));

                                return;
                            }

                            $normalizedIndex = (int) $index;

                            if (! array_key_exists($normalizedIndex, $question->options ?? [])) {
                                $fail(__('validation.invalid_choice'));
                            }
                        }
                    } elseif ($question->type === QuestionType::Essay) {
                        if (! is_string($value)) {
                            $fail(__('validation.invalid_format'));
                        }
                    } elseif ($question->type === QuestionType::FileUpload) {
                        if (! request()->hasFile('answer')) {
                            $fail(__('validation.invalid_format'));
                        }
                    }
                },
            ], 
        ];
    }

    public function attributes(): array
    {
        return [
            'question_id' => __('validation.attributes.question_id'),
            'answer' => __('validation.attributes.answer'),
        ];
    }
}
