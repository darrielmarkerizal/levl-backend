<?php

namespace Modules\Schemes\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait HasSchemesRequestRules
{
    protected function rulesCourse(int $courseId = 0): array
    {
        $uniqueCode = Rule::unique('courses', 'code')->whereNull('deleted_at');
        $uniqueSlug = Rule::unique('courses', 'slug')->whereNull('deleted_at');
        if ($courseId > 0) {
            $uniqueCode = $uniqueCode->ignore($courseId);
            $uniqueSlug = $uniqueSlug->ignore($courseId);
        }

        return [
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'slug' => ['nullable', 'string', 'max:100', $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'short_desc' => ['nullable', 'string'],
            'level_tag' => ['required', Rule::in(['dasar', 'menengah', 'mahir'])],
            'type' => ['required', Rule::in(['okupasi', 'kluster'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'progression_mode' => ['required', Rule::in(['sequential', 'free'])],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'outcomes' => ['sometimes', 'array'],
            'outcomes.*' => ['string'],
            'prereq' => ['sometimes', 'array'],
            'prereq.*' => ['string'],
            'thumbnail' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'banner' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'instructor_id' => ['sometimes', 'integer', 'exists:users,id'],
            'course_admins' => ['sometimes', 'array'],
            'course_admins.*' => ['integer', 'exists:users,id'],
        ];
    }

    protected function messagesCourse(): array
    {
        return [
            'code.required' => 'Kode wajib diisi.',
            'code.unique' => 'Kode sudah digunakan.',
            'title.required' => 'Judul wajib diisi.',
            'level_tag.required' => 'Level wajib diisi.',
            'visibility.required' => 'Visibility wajib diisi.',
            'type.required' => 'Tipe wajib diisi.',
            'progression_mode.required' => 'Mode progres wajib diisi.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'status.in' => 'Status tidak valid.',
            'type.in' => 'Tipe tidak valid.',
            'thumbnail.image' => 'Thumbnail harus berupa gambar.',
            'banner.image' => 'Banner harus berupa gambar.',
            'instructor_id.exists' => 'Instruktur tidak ditemukan.',
            'course_admins.*.exists' => 'Admin course tidak ditemukan.',
            'course_admins.*.integer' => 'Setiap item course_admins harus berupa angka (ID pengguna).',
            'outcomes.*.string' => 'Setiap item outcomes harus berupa teks.',
            'prereq.*.string' => 'Setiap item prereq harus berupa teks.',
            'tags.*.string' => 'Setiap item tags harus berupa teks.',
            'tags.array' => 'Field tags harus berupa array.',
            'outcomes.array' => 'Field outcomes harus berupa array.',
            'prereq.array' => 'Field prereq harus berupa array.',
            'course_admins.array' => 'Field course_admins harus berupa array.',
        ];
    }
}
