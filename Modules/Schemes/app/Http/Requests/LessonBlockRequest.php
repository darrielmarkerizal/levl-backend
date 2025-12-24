<?php

namespace Modules\Schemes\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;

class LessonBlockRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxMb = config('app.lesson_block_max_upload_mb', 50);
        $maxKb = $maxMb * 1024;

        return [
            'type' => 'required|in:text,video,image,file',
            'content' => 'nullable|string',
            'order' => 'nullable|integer|min:1',
            'media' => [
                'nullable',
                'file',
                'max:'.$maxKb,
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    if (in_array($type, ['video', 'image', 'file']) && ! $value) {
                        $fail('File media wajib diunggah untuk tipe ini.');
                    }
                },
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $type = $this->input('type');
                    $mime = $value->getMimeType();
                    $ok = true;
                    if ($type === 'image') {
                        $ok = str_starts_with($mime, 'image/');
                    } elseif ($type === 'video') {
                        $ok = str_starts_with($mime, 'video/');
                    }
                    if (! $ok) {
                        $fail('Tipe file tidak sesuai dengan tipe block.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Field type wajib diisi.',
            'type.in' => 'Field type hanya boleh: text, video, image, file.',
            'content.string' => 'Field content harus berupa teks.',
            'order.integer' => 'Field order harus berupa angka.',
            'order.min' => 'Field order minimal bernilai 1.',
            'media.file' => 'Field media harus berupa file yang valid.',
            'media.max' => 'Ukuran file media melebihi batas yang diizinkan.',
        ];
    }
}
