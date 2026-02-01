<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.*' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:50', 'regex:/^-?[a-z_]+$/i'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.array' => __('messages.validations.filter_array'),
            'filter.*.string' => __('messages.validations.filter_string'),
            'sort.regex' => __('messages.validations.sort_regex'),
            'page.integer' => __('messages.validations.page_integer'),
            'page.min' => __('messages.validations.page_min'),
            'per_page.integer' => __('messages.validations.per_page_integer'),
            'per_page.min' => __('messages.validations.per_page_min'),
            'per_page.max' => __('messages.validations.per_page_max'),
            'search.string' => __('messages.validations.search_string'),
        ];
    }

    public function attributes(): array
    {
        return [
            'filter' => 'filter',
            'sort' => 'sort',
            'page' => 'page',
            'per_page' => 'per_page',
            'search' => 'search',
        ];
    }
}
