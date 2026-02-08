<?php

declare(strict_types=1);

namespace Modules\Forums\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'min:1',
                'max:5000',
                function ($attribute, $value, $fail) {
                    $this->validateMentionedUsernames($value, $fail);
                },
            ],
        ];
    }

    private function validateMentionedUsernames(string $content, $fail): void
    {
        preg_match_all('/@([a-zA-Z0-9._-]+)/', $content, $matches);
        
        if (empty($matches[1])) {
            return;
        }

        $mentionedUsernames = array_unique($matches[1]);
        $existingUsernames = \Modules\Auth\Models\User::whereIn('username', $mentionedUsernames)
            ->pluck('username')
            ->toArray();

        $invalidUsernames = array_diff($mentionedUsernames, $existingUsernames);

        if (!empty($invalidUsernames)) {
            $fail(__('validation.mentioned_users_not_found', [
                'usernames' => implode(', ', array_map(fn($u) => "@{$u}", $invalidUsernames))
            ]));
        }
    }

    public function messages(): array
    {
        return [
            'content.required' => __('validation.required', ['attribute' => __('validation.attributes.content')]),
            'content.min' => __('validation.min.string', ['attribute' => __('validation.attributes.content'), 'min' => 1]),
            'content.max' => __('validation.max.string', ['attribute' => __('validation.attributes.content'), 'max' => 5000]),
        ];
    }
}
