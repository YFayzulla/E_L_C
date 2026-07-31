<?php

namespace App\Http\Requests\Homework;

use App\Models\Homework;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The grading sheet posts three arrays KEYED BY STUDENT ID:
 * status[42], score[42], comment[42]. Never index-parallel arrays — a roster
 * that changes between render and submit would silently shift every grade.
 */
class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'    => ['nullable', 'array'],
            'status.*'  => ['nullable', Rule::in(array_keys(config('grading.homework_status', [])))],
            'score'     => ['nullable', 'array'],
            'score.*'   => ['nullable', 'integer', 'min:0', 'max:' . $this->maxScore()],
            'comment'   => ['nullable', 'array'],
            // 500, not 1000: homework_submissions.comment is VARCHAR(500) and
            // MySQL runs in strict mode, so anything longer aborts the whole
            // upsert with "Data too long" and loses the entire grading sheet.
            'comment.*' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.*.in'         => 'Topshirish holati noto‘g‘ri tanlangan.',
            'score.*.integer'     => 'Ball butun son bo‘lishi kerak.',
            'score.*.min'         => 'Ball manfiy bo‘lmasin.',
            'score.*.max'         => 'Ball :max dan oshmasin.',
            'comment.*.max'       => 'Izoh 500 belgidan oshmasin.',
        ];
    }

    /**
     * The assignment's own ceiling — the route model is already resolved by the
     * time a form request validates.
     */
    private function maxScore(): int
    {
        $homework = $this->route('homework');

        return $homework instanceof Homework && $homework->max_score > 0
            ? (int) $homework->max_score
            : 100;
    }
}
