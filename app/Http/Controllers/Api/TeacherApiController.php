<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LessonAndHistory;
use App\Models\LessonSkillGrade;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Teacher-facing mobile API.
 *
 * Every group-scoped action runs assertTeachesGroup() — the token proves who
 * you are, not which groups are yours, so the same relationship check the web
 * uses applies here unchanged.
 */
class TeacherApiController extends ApiController
{
    use AuthorizesGroupAccess;

    /* ================================================================= groups */

    public function groups(Request $request)
    {
        $groups = Group::whereIn('id', $this->accessibleGroupIds())
            ->with('room:id,room')
            ->withCount(['students as members_count'])
            ->orderBy('name')
            ->get();

        return $this->ok($groups->map(fn(Group $g) => [
            'id'            => $g->id,
            'name'          => $g->name,
            'students'      => (int) $g->members_count,
            'start_time'    => $g->start_time,
            'finish_time'   => $g->finish_time,
            'room'          => $g->room?->room,
            'is_finished'   => $g->isFinished(),
            'status_label'  => $g->statusLabel(),
        ]));
    }

    public function groupStudents(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $students = User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'photo', 'mark']);

        return $this->ok($students->map(fn(User $s) => [
            'id'    => $s->id,
            'name'  => $s->name,
            'phone' => $s->phone,
            'photo' => $s->photo ? asset('storage/' . $s->photo) : null,
            'mark'  => $s->mark,
        ]));
    }

    /* ============================================================= attendance */

    /**
     * Today's register: the roster plus whatever was already marked today, so
     * the app opens with the current state rather than a blank sheet.
     */
    public function attendanceSheet(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $model = Group::findOrFail($group);

        $students = User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->orderBy('name')
            ->get(['id', 'name', 'photo']);

        $lesson = LessonAndHistory::where('group', $group)
            ->where('data', 1)
            ->whereDate('created_at', today())
            ->first();

        // Rows exist only for absent(0) / late(2); present is implicit.
        $marked = $lesson
            ? Attendance::where('lesson_id', $lesson->id)
                ->pluck('status', 'user_id')
            : collect();

        return $this->ok([
            'group'  => ['id' => $model->id, 'name' => $model->name],
            'date'   => today()->format('Y-m-d'),
            'lesson' => $lesson ? ['id' => $lesson->id, 'name' => $lesson->name] : null,
            'already_taken' => $lesson !== null,
            'students' => $students->map(fn(User $s) => [
                'id'     => $s->id,
                'name'   => $s->name,
                'photo'  => $s->photo ? asset('storage/' . $s->photo) : null,
                // 1 = present (default), 0 = absent, 2 = late
                'status' => (int) ($marked[$s->id] ?? 1),
            ]),
        ]);
    }

    /**
     * Idempotent submit — the same contract as the web:
     * one lesson row per group per day, present deletes the row.
     */
    public function submitAttendance(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $data = $request->validate([
            'statuses'   => ['required', 'array', 'min:1'],
            'statuses.*' => ['required', 'integer', Rule::in([0, 1, 2])],
            'lesson'     => ['nullable', 'string', 'max:255'],
        ], [
            'statuses.required' => 'Davomat ma’lumoti yuborilmadi.',
        ]);

        $model = Group::findOrFail($group);

        // Only real members of THIS group — a crafted payload must not be able
        // to mark a stranger.
        $roster = User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->pluck('id')
            ->all();

        DB::beginTransaction();

        try {
            $lesson = LessonAndHistory::where('group', $group)
                ->where('data', 1)
                ->whereDate('created_at', today())
                ->first();

            if (! $lesson) {
                $lesson = LessonAndHistory::create([
                    // `nullable` means "may be null", NOT "is always present":
                    // an app that omits the field entirely leaves no key here.
                    'name'  => ($data['lesson'] ?? null) ?: 'Dars: ' . now()->format('d.m.Y'),
                    'data'  => 1,
                    'group' => $group,
                ]);
            }

            $absent = 0;
            $late = 0;

            foreach ($data['statuses'] as $userId => $status) {
                $userId = (int) $userId;

                if (! in_array($userId, $roster, true)) {
                    continue;
                }

                if ((int) $status === 1) {
                    Attendance::where('user_id', $userId)
                        ->where('lesson_id', $lesson->id)
                        ->delete();

                    continue;
                }

                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'lesson_id' => $lesson->id],
                    [
                        'group_id'    => $group,
                        'who_checked' => auth()->id(),
                        'status'      => (int) $status,
                    ]
                );

                (int) $status === 2 ? $late++ : $absent++;
            }

            DB::commit();

            return $this->ok([
                'lesson_id' => $lesson->id,
                'absent'    => $absent,
                'late'      => $late,
                'present'   => count($roster) - $absent - $late,
            ], 'Davomat saqlandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherApiController@submitAttendance error: ' . $e->getMessage());

            return $this->fail('Davomatni saqlashda xatolik yuz berdi.', 500);
        }
    }

    /* =============================================================== homework */

    public function homework(Request $request)
    {
        $items = Homework::whereIn('group_id', $this->accessibleGroupIds())
            ->with('group:id,name')
            ->withCount([
                'submissions as submitted_count' => fn($q) => $q->whereNotNull('submitted_at'),
                'submissions as graded_count'    => fn($q) => $q->whereNotNull('score'),
            ])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->ok($items->map(fn(Homework $h) => [
            'id'          => $h->id,
            'title'       => $h->title,
            'description' => $h->description,
            'group'       => ['id' => $h->group_id, 'name' => $h->group?->name],
            'due_date'    => $h->due_date?->format('Y-m-d'),
            'due_label'   => $h->dueLabel(),
            'is_overdue'  => $h->isOverdue(),
            'max_score'   => $h->max_score,
            'submitted'   => (int) $h->submitted_count,
            'graded'      => (int) $h->graded_count,
        ]));
    }

    public function storeHomework(Request $request)
    {
        $data = $request->validate([
            'group_id'    => ['required', 'integer', 'exists:groups,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date'    => ['nullable', 'date'],
            'max_score'   => ['nullable', 'integer', 'min:1', 'max:1000'],
        ], [
            'title.required'    => 'Sarlavhani kiriting.',
            'group_id.required' => 'Guruhni tanlang.',
        ]);

        $this->assertTeachesGroup((int) $data['group_id']);

        try {
            $homework = Homework::create([
                'group_id'    => $data['group_id'],
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date'    => $data['due_date'] ?? null,
                'max_score'   => $data['max_score'] ?? 100,
                'allow_file'  => true,
                'created_by'  => auth()->id(),
            ]);

            return $this->ok(['id' => $homework->id], 'Uy vazifasi yaratildi.', 201);
        } catch (\Exception $e) {
            Log::error('TeacherApiController@storeHomework error: ' . $e->getMessage());

            return $this->fail('Uy vazifasini saqlashda xatolik.', 500);
        }
    }

    /** The grading sheet: full roster with each student's submission. */
    public function homeworkSubmissions(Request $request, Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        $submissions = $homework->submissions()->get()->keyBy('user_id');

        $roster = $homework->roster()->get(['users.id', 'users.name', 'users.photo']);

        return $this->ok([
            'homework' => [
                'id'        => $homework->id,
                'title'     => $homework->title,
                'max_score' => $homework->max_score,
                'due_label' => $homework->dueLabel(),
            ],
            'students' => $roster->map(function (User $s) use ($submissions) {
                $sub = $submissions->get($s->id);

                return [
                    'id'              => $s->id,
                    'name'            => $s->name,
                    'photo'           => $s->photo ? asset('storage/' . $s->photo) : null,
                    'status'          => (int) ($sub->status ?? 0),
                    'status_label'    => $sub?->statusLabel() ?? 'Topshirmagan',
                    'submitted_at'    => $sub?->submitted_at?->format('d.m.Y H:i'),
                    'submission_text' => $sub?->submission_text,
                    'submission_file' => $sub?->submission_file
                        ? asset('storage/' . $sub->submission_file)
                        : null,
                    'score'           => $sub?->score,
                    'comment'         => $sub?->comment,
                ];
            }),
        ]);
    }

    public function gradeHomework(Request $request, Homework $homework)
    {
        $this->assertTeachesGroup((int) $homework->group_id);

        $data = $request->validate([
            'grades'             => ['required', 'array', 'min:1'],
            'grades.*.user_id'   => ['required', 'integer'],
            'grades.*.status'    => ['nullable', 'integer', Rule::in([0, 1, 2])],
            'grades.*.score'     => ['nullable', 'integer', 'min:0', 'max:' . $homework->max_score],
            'grades.*.comment'   => ['nullable', 'string', 'max:500'],
        ], [
            'grades.*.score.max' => 'Ball ' . $homework->max_score . ' dan oshmasligi kerak.',
        ]);

        $roster = $homework->roster()->pluck('users.id')->all();
        $now = now();
        $rows = [];

        foreach ($data['grades'] as $grade) {
            if (! in_array((int) $grade['user_id'], $roster, true)) {
                continue;
            }

            $rows[] = [
                'homework_id' => $homework->id,
                'user_id'     => (int) $grade['user_id'],
                'status'      => (int) ($grade['status'] ?? 0),
                'score'       => $grade['score'] ?? null,
                'comment'     => $grade['comment'] ?? null,
                'graded_by'   => auth()->id(),
                'graded_at'   => $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if (empty($rows)) {
            return $this->fail('Baholanadigan talaba topilmadi.', 422);
        }

        try {
            // Upsert on (homework_id, user_id): re-grading is idempotent and the
            // student's own submission columns are left untouched.
            HomeworkSubmission::upsert(
                $rows,
                ['homework_id', 'user_id'],
                ['status', 'score', 'comment', 'graded_by', 'graded_at', 'updated_at']
            );

            return $this->ok(['graded' => count($rows)], count($rows) . ' ta baho saqlandi.');
        } catch (\Exception $e) {
            Log::error('TeacherApiController@gradeHomework error: ' . $e->getMessage());

            return $this->fail('Baholarni saqlashda xatolik.', 500);
        }
    }

    /* ================================================================= skills */

    /** Lessons a skill grade can attach to. Never creates one. */
    public function lessons(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $lessons = LessonAndHistory::where('group', $group)
            ->where('data', 1)
            ->where('created_at', '>=', now()->subDays(60))
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'name', 'created_at']);

        return $this->ok($lessons->map(fn($l) => [
            'id'    => $l->id,
            'name'  => $l->name,
            'date'  => $l->created_at?->format('d.m.Y'),
            'label' => trim(($l->name ?: 'Dars') . ' — ' . $l->created_at?->format('d.m.Y')),
        ]));
    }

    public function skillSheet(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $lessonId = (int) $request->query('lesson_id');

        $students = User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->orderBy('name')
            ->get(['id', 'name']);

        $existing = $lessonId
            ? LessonSkillGrade::where('lesson_id', $lessonId)->get()->groupBy('user_id')
            : collect();

        $skills = (array) config('grading.skills');
        $labels = (array) config('grading.skill_labels');

        return $this->ok([
            'skills' => array_map(fn($s) => ['key' => $s, 'label' => $labels[$s] ?? $s], $skills),
            'students' => $students->map(function (User $s) use ($existing, $skills) {
                $rows = $existing->get($s->id, collect())->keyBy('skill');

                return [
                    'id'      => $s->id,
                    'name'    => $s->name,
                    'scores'  => collect($skills)
                        ->mapWithKeys(fn($k) => [$k => $rows->get($k)?->score])
                        ->all(),
                    'comment' => $rows->first()?->comment,
                ];
            }),
        ]);
    }

    public function storeSkills(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $data = $request->validate([
            'lesson_id'           => ['required', 'integer'],
            'grades'              => ['required', 'array', 'min:1'],
            'grades.*.user_id'    => ['required', 'integer'],
            'grades.*.skill'      => ['required', 'string', Rule::in((array) config('grading.skills'))],
            'grades.*.score'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'grades.*.comment'    => ['nullable', 'string', 'max:255'],
        ]);

        // The lesson must belong to THIS group, otherwise a teacher could graft
        // grades onto another group's lesson.
        $valid = LessonAndHistory::where('id', $data['lesson_id'])
            ->where('group', $group)
            ->where('data', 1)
            ->exists();

        if (! $valid) {
            return $this->fail('Bu dars ushbu guruhga tegishli emas.', 422);
        }

        $roster = User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->pluck('id')
            ->all();

        $now = now();
        $rows = [];

        foreach ($data['grades'] as $g) {
            // A blank score means "not assessed" — skip it rather than store 0.
            if (($g['score'] ?? null) === null) {
                continue;
            }

            if (! in_array((int) $g['user_id'], $roster, true)) {
                continue;
            }

            $rows[] = [
                'lesson_id'  => $data['lesson_id'],
                'group_id'   => $group,
                'user_id'    => (int) $g['user_id'],
                'skill'      => $g['skill'],
                'score'      => (int) $g['score'],
                'comment'    => $g['comment'] ?? null,
                'graded_by'  => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return $this->fail('Hech qanday baho kiritilmadi.', 422);
        }

        try {
            LessonSkillGrade::upsert(
                $rows,
                ['lesson_id', 'user_id', 'skill'],
                ['score', 'comment', 'graded_by', 'updated_at']
            );

            return $this->ok(['saved' => count($rows)], count($rows) . ' ta baho saqlandi.');
        } catch (\Exception $e) {
            Log::error('TeacherApiController@storeSkills error: ' . $e->getMessage());

            return $this->fail('Baholarni saqlashda xatolik.', 500);
        }
    }

    /* =============================================================== progress */

    public function groupProgress(Request $request, int $group, ProgressService $progress)
    {
        $this->assertTeachesGroup($group);

        $data = $progress->forGroup($group);

        return $this->ok([
            'buckets' => $data['buckets'],
            'current' => $data['current'],
            'delta'   => $data['delta'],
            'average' => $data['average'] ?? null,
            'students' => collect($data['students'])->map(fn($row, $id) => [
                'id'         => (int) $id,
                'name'       => $row['name'],
                'current'    => $row['current'],
                'average'    => $row['average'] ?? null,
                'delta'      => $row['delta'],
                'rank'       => $row['rank'] ?? null,
                'components' => $row['components'] ?? [],
            ])->values(),
        ]);
    }
}
