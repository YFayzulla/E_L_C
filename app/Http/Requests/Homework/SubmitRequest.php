<?php

namespace App\Http\Requests\Homework;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A student hands in their work. Only the two student-owned columns are
 * accepted here — status, score, comment and graded_* are decided server-side
 * (see HomeworkController@submit) and can never be posted by a student.
 */
class SubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $upload = config('grading.homework_upload');

        return [
            'submission_text' => ['nullable', 'string', 'max:20000'],
            'submission_file' => [
                'nullable',
                'file',
                'mimes:' . implode(',', $upload['mimes']),
                'max:' . $upload['max_kb'],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (filled($this->input('submission_text')) || $this->hasFile('submission_file')) {
                return;
            }

            // Re-saving is fine as long as something is already on file.
            if ($this->alreadyHasWorkOnFile()) {
                return;
            }

            $validator->errors()->add(
                'submission_text',
                'Javob matnini yozing yoki fayl yuklang.'
            );
        });
    }

    public function attributes(): array
    {
        return [
            'submission_text' => 'javob matni',
            'submission_file' => 'fayl',
        ];
    }

    public function messages(): array
    {
        $upload = config('grading.homework_upload');

        return [
            'submission_text.max'   => 'Javob matni juda uzun.',
            'submission_file.mimes' => 'Ruxsat etilgan fayl turlari: ' . implode(', ', $upload['mimes']) . '.',
            'submission_file.max'   => 'Fayl hajmi ' . round($upload['max_kb'] / 1024) . ' MB dan oshmasin.',
        ];
    }

    private function alreadyHasWorkOnFile(): bool
    {
        $homework = $this->route('homework');

        if (! $homework instanceof Homework || ! $this->user()) {
            return false;
        }

        return HomeworkSubmission::where('homework_id', $homework->id)
            ->where('user_id', $this->user()->id)
            ->where(function ($query) {
                $query->whereNotNull('submission_file')
                    ->orWhereNotNull('submission_text');
            })
            ->exists();
    }
}
