<?php

namespace App\Http\Requests\SkillGrade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The students x skills matrix posted from teacher/skills/{group}.
 *
 * Shape:
 *   lesson_id                       an EXISTING lesson_and_histories row
 *   score[<user id>][<skill>]       0-100, or blank meaning "not assessed"
 *   comment[<user id>]              free text, one per student
 *
 * Authorisation lives in the controller (assertTeachesGroup + a check that the
 * lesson really belongs to that group) — a form request cannot see the route
 * parameter early enough to be the only guard.
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Collect the skill keys the browser actually sent so they can be checked
     * against config('grading.skills') with a plain Rule::in — validating array
     * KEYS is not otherwise expressible in Laravel's rule syntax.
     */
    protected function prepareForValidation(): void
    {
        $posted = [];

        foreach ((array) $this->input('score', []) as $bySkill) {
            if (is_array($bySkill)) {
                foreach (array_keys($bySkill) as $skill) {
                    $posted[(string) $skill] = true;
                }
            }
        }

        $this->merge(['posted_skills' => array_keys($posted)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'lesson_id'       => ['required', 'integer', 'exists:lesson_and_histories,id'],
            'score'           => ['nullable', 'array'],
            'comment'         => ['nullable', 'array'],
            'comment.*'       => ['nullable', 'string', 'max:250'],
            'posted_skills'   => ['array'],
            'posted_skills.*' => [Rule::in(config('grading.skills', []))],
        ];

        // One rule per known skill: score[<user>][<skill>] must be 0-100 or blank.
        foreach ((array) config('grading.skills', []) as $skill) {
            $rules['score.*.' . $skill] = ['nullable', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'Darsni tanlang.',
            'lesson_id.exists'   => 'Tanlangan dars topilmadi.',
            'posted_skills.*.in' => 'Noma’lum ko‘nikma yuborildi.',
            'score.*.*.integer'  => 'Baho butun son bo‘lishi kerak.',
            'score.*.*.min'      => 'Baho 0 dan kichik bo‘lmasligi kerak.',
            'score.*.*.max'      => 'Baho 100 dan katta bo‘lmasligi kerak.',
            'comment.*.max'      => 'Izoh 250 belgidan oshmasligi kerak.',
        ];
    }
}
