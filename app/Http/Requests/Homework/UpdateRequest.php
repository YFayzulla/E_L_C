<?php

namespace App\Http\Requests\Homework;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Teacher edits an existing assignment. Same shape as StoreRequest plus the
 * "remove the current attachment" checkbox.
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $upload = config('grading.homework_upload');

        return [
            'group_id'          => ['required', 'integer', Rule::exists('groups', 'id')],
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:10000'],
            'attachment'        => [
                'nullable',
                'file',
                'mimes:' . implode(',', $upload['mimes']),
                'max:' . $upload['max_kb'],
            ],
            'remove_attachment' => ['nullable', 'boolean'],
            'due_date'          => ['nullable', 'date'],
            'max_score'         => ['required', 'integer', 'min:1', 'max:1000'],
            'allow_file'        => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_file'        => $this->boolean('allow_file'),
            'remove_attachment' => $this->boolean('remove_attachment'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'group_id'    => 'guruh',
            'title'       => 'sarlavha',
            'description' => 'topshiriq matni',
            'attachment'  => 'ilova',
            'due_date'    => 'muddat',
            'max_score'   => 'maksimal ball',
        ];
    }

    public function messages(): array
    {
        $upload = config('grading.homework_upload');

        return [
            'group_id.required'  => 'Guruhni tanlang.',
            'group_id.exists'    => 'Tanlangan guruh topilmadi.',
            'title.required'     => 'Vazifa sarlavhasini kiriting.',
            'title.max'          => 'Sarlavha 255 belgidan oshmasin.',
            'attachment.mimes'   => 'Ruxsat etilgan fayl turlari: ' . implode(', ', $upload['mimes']) . '.',
            'attachment.max'     => 'Fayl hajmi ' . round($upload['max_kb'] / 1024) . ' MB dan oshmasin.',
            'due_date.date'      => 'Muddat sanasi noto‘g‘ri.',
            'max_score.required' => 'Maksimal ballni kiriting.',
            'max_score.min'      => 'Maksimal ball kamida 1 bo‘lsin.',
            'max_score.max'      => 'Maksimal ball 1000 dan oshmasin.',
        ];
    }
}
