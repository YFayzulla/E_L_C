<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    protected $fillable = [
        'user_id', 'get_mark', 'skill', 'group', 'overall_result',
        'for_what', 'rec_group', 'history_id',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * What this mark measured, ready to show.
     *
     * A skill when one was chosen; otherwise the old free-text note, so rows
     * written before the skill column existed still read sensibly. "Umumiy"
     * when there is neither.
     */
    public function skillLabel(): string
    {
        if (filled($this->skill)) {
            $labels = (array) config('grading.skill_labels');

            return $labels[$this->skill] ?? $this->skill;
        }

        return filled($this->for_what) ? $this->for_what : 'Umumiy';
    }

    /**
     * Talabaning testlari — bitta imtihon bitta yozuv, eng yangisi tepada.
     *
     * Cheklov qatorlarga emas, testlarga qo'yiladi: SQL'dagi limit beshta
     * ko'nikmali testni yarmidan kesib qo'yardi.
     */
    public static function testsFor(int $userId, ?int $limit = null)
    {
        $rows = static::where('assessments.user_id', $userId)
            ->leftJoin('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
            ->select('assessments.*', 'lesson_and_histories.name as test_name')
            ->orderByDesc('assessments.created_at')
            ->orderByDesc('assessments.id')
            ->get();

        $tests = static::groupByTest($rows);

        return $limit === null ? $tests : $tests->take($limit)->values();
    }

    /**
     * Bitta imtihonning ko'nikma qatorlarini bitta yozuvga yig'adi.
     *
     * `assessments` da har bir ko'nikma alohida qator (bitta test — besh
     * qator). Jadvallarda esa bitta imtihon bitta qator bo'lishi kerak:
     * qaysi ko'nikmalar topshirilgani, ularning ballari va o'rtachasi.
     * Qatorlar `test_name` bilan kelishi kutiladi (lesson_and_histories
     * join), tartib — chaqiruvchiniki (odatda eng yangisi tepada).
     *
     * @param  iterable<self>  $rows
     * @return \Illuminate\Support\Collection<int, object{test_name: string, group: ?string, date: ?\Illuminate\Support\Carbon,
     *         marks: array<int, array{skill: ?string, label: string, mark: int}>, average: ?int, comment: ?string}>
     */
    public static function groupByTest(iterable $rows)
    {
        $order = array_flip((array) config('grading.skills'));

        return collect($rows)
            // history_id yo'q eski qatorlar o'zicha alohida yozuv.
            ->groupBy(fn (self $row) => $row->history_id ? 'h' . $row->history_id : 'a' . $row->id)
            ->map(function ($items) use ($order) {
                $first = $items->first();

                $marks = $items
                    ->sortBy(fn (self $row) => [$order[$row->skill] ?? PHP_INT_MAX, $row->id])
                    ->map(fn (self $row) => [
                        'skill' => $row->skill,
                        'label' => filled($row->skill) ? $row->skillLabel() : 'Umumiy',
                        'mark'  => (int) $row->get_mark,
                    ])
                    ->values()
                    ->all();

                $values = array_column($marks, 'mark');

                return (object) [
                    'test_name' => $first->test_name ?? 'Test',
                    'group'     => $first->group,
                    'date'      => $items->max('created_at'),
                    'marks'     => $marks,
                    'average'   => $values === [] ? null : (int) round(array_sum($values) / count($values)),
                    // Yangi formada for_what — o'qituvchi izohi; skill bor
                    // qatorlarda u izoh, aks holda eski "maqsad" matni.
                    'comment'   => $items->first(fn (self $row) => filled($row->skill) && filled($row->for_what))?->for_what,
                ];
            })
            ->values();
    }

    /** Only the marks that measured a named skill. */
    public function scopeForSkill($query, string $skill)
    {
        return $query->where('skill', $skill);
    }
}
