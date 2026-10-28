<?php

namespace App\Http\Requests\SkillGrade;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ScoreScale;
use Illuminate\Validation\Rule;

/**
 * The students x skills matrix posted from teacher/skills/{group}.
 *
 * Shape:
 *   lesson_id                       an EXISTING lesson_and_histories row
 *   max[<skill>]                    maksimal ball (standart — markaz sozlamasi)
 *   score[<user id>][<skill>]       0..max, or blank meaning "not assessed"
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
            // Ustun tepasidagi belgilar: dars qaysi ko'nikmalarni qamradi.
            'skills'          => ['required', 'array', 'min:1'],
            'skills.*'        => [Rule::in(config('grading.skills', []))],
            'score'           => ['nullable', 'array'],
            'comment'         => ['nullable', 'array'],
            'comment.*'       => ['nullable', 'string', 'max:250'],
            'posted_skills'   => ['array'],
            'posted_skills.*' => [Rule::in(config('grading.skills', []))],
        ];

        $limit = (int) config('grading.skill_max_limit', 1000);
        $rules['max']   = ['nullable', 'array'];
        $rules['max.*'] = ['nullable', 'integer', 'min:1', "max:{$limit}"];

        // One rule per known skill: score[<user>][<skill>] must be 0..max or
        // blank. Maksimum — ustun sarlavhasidagi, bo'lmasa markaz sozlamasi.
        foreach ($this->maxes() as $skill => $max) {
            $rules['score.*.' . $skill] = ['nullable', 'integer', 'min:0', "max:{$max}"];
        }

        return $rules;
    }

    /**
     * Ko'nikma => shu dars uchun maksimal ball.
     *
     * @return array<string, int>
     */
    public function maxes(): array
    {
        $out = [];

        foreach ((array) config('grading.skills', []) as $skill) {
            $out[$skill] = ScoreScale::clampMax($this->input("max.{$skill}") ?: ScoreScale::max($skill));
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'Darsni tanlang.',
            'lesson_id.exists'   => 'Tanlangan dars topilmadi.',
            'skills.required'    => 'Kamida bitta ko‘nikma ustunini belgilang.',
            'skills.min'         => 'Kamida bitta ko‘nikma ustunini belgilang.',
            'skills.*.in'        => 'Noma’lum ko‘nikma tanlandi.',
            'posted_skills.*.in' => 'Noma’lum ko‘nikma yuborildi.',
            'score.*.*.integer'  => 'Baho butun son bo‘lishi kerak.',
            'score.*.*.min'      => 'Baho 0 dan kichik bo‘lmasligi kerak.',
            'score.*.*.max'      => 'Baho shu ustunning maksimal balidan oshmasligi kerak.',
            'max.*.min'          => 'Maksimal ball kamida 1 bo‘lishi kerak.',
            'comment.*.max'      => 'Izoh 250 belgidan oshmasligi kerak.',
        ];
    }
}
