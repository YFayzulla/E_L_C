<?php

namespace App\Http\Controllers\Api;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Group;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LessonSkillGrade;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Student-facing mobile API.
 *
 * Everything is implicitly scoped to the authenticated student — there is no
 * id in any path, so there is nothing to tamper with.
 */
class StudentApiController extends ApiController
{
    public function dashboard(Request $request, ProgressService $progress)
    {
        $me = $request->user()->loadMissing(['groups.teachers', 'deptStudent']);

        $absencesThisMonth = Attendance::where('user_id', $me->id)
            ->whereIn('status', [0, 2])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $pendingHomework = Homework::whereIn('group_id', $me->groups->pluck('id'))
            ->whereDoesntHave('submissions', fn($q) => $q
                ->where('user_id', $me->id)
                ->whereNotNull('submitted_at'))
            ->count();

        $p = $progress->forStudent($me->id);

        return $this->ok([
            'name'              => $me->name,
            'attendance_rate'   => $me->attendanceRate(),
            'absences_month'    => $absencesThisMonth,
            'last_mark'         => $me->mark,
            'is_paid'           => $me->isPaidThisMonth(),
            'debt'              => (int) optional($me->deptStudent)->dept,
            'pending_homework'  => $pendingHomework,
            'progress'          => [
                'current'    => $p['current'],
                'delta'      => $p['delta'],
                'buckets'    => $p['buckets'],
                'components' => $p['components'],
            ],
            'groups' => $me->groups->map(fn(Group $g) => [
                'id'          => $g->id,
                'name'        => $g->name,
                'teachers'    => $g->teachers->pluck('name')->implode(', '),
                'start_time'  => $g->start_time,
                'finish_time' => $g->finish_time,
            ]),
        ]);
    }

    public function attendance(Request $request)
    {
        $rows = Attendance::where('user_id', $request->user()->id)
            ->whereIn('status', [0, 2])
            ->with(['group:id,name', 'lesson:id,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return $this->ok($rows->map(fn(Attendance $a) => [
            'id'     => $a->id,
            'date'   => $a->created_at?->format('d.m.Y'),
            'group'  => $a->group?->name,
            'lesson' => $a->lesson?->name,
            'status' => (int) $a->status,
            'label'  => (int) $a->status === 2 ? 'Kechikdi' : 'Kelmadi',
        ]));
    }

    public function homework(Request $request)
    {
        $me = $request->user();
        $groupIds = $me->groups()->pluck('groups.id');

        $items = Homework::whereIn('group_id', $groupIds)
            ->with('group:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $mine = HomeworkSubmission::where('user_id', $me->id)
            ->whereIn('homework_id', $items->pluck('id'))
            ->get()
            ->keyBy('homework_id');

        return $this->ok($items->map(function (Homework $h) use ($mine) {
            $sub = $mine->get($h->id);

            return [
                'id'           => $h->id,
                'title'        => $h->title,
                'description'  => $h->description,
                'group'        => $h->group?->name,
                'due_date'     => $h->due_date?->format('Y-m-d'),
                'due_label'    => $h->dueLabel(),
                'is_overdue'   => $h->isOverdue(),
                'max_score'    => $h->max_score,
                'attachment'   => $h->attachment ? asset('storage/' . $h->attachment) : null,
                'submitted'    => $sub?->submitted_at !== null,
                'submitted_at' => $sub?->submitted_at?->format('d.m.Y H:i'),
                'my_text'      => $sub?->submission_text,
                'my_file'      => $sub?->submission_file ? asset('storage/' . $sub->submission_file) : null,
                'score'        => $sub?->score,
                'comment'      => $sub?->comment,
                'status_label' => $sub?->score !== null
                    ? 'Baholandi'
                    : ($sub?->submitted_at ? 'Topshirildi' : 'Topshirilmagan'),
            ];
        }));
    }

    public function submitHomework(Request $request, Homework $homework)
    {
        $me = $request->user();

        // The assignment must belong to a group this student is actually in.
        $mine = $me->groups()->where('groups.id', $homework->group_id)->exists();

        abort_unless($mine, 403, 'Bu vazifa sizning guruhingizga tegishli emas.');

        $upload = (array) config('grading.homework_upload');

        $data = $request->validate([
            'submission_text' => ['nullable', 'string', 'max:5000'],
            'file'            => [
                'nullable', 'file',
                'mimes:' . implode(',', $upload['mimes'] ?? ['pdf', 'jpg', 'png']),
                'max:' . ($upload['max_kb'] ?? 5120),
            ],
        ], [
            'file.mimes' => 'Fayl turi qo‘llab-quvvatlanmaydi.',
            'file.max'   => 'Fayl juda katta.',
        ]);

        if (blank($data['submission_text'] ?? null) && ! $request->hasFile('file')) {
            return $this->fail('Matn yozing yoki fayl biriktiring.', 422);
        }

        if ($request->hasFile('file') && ! $homework->allow_file) {
            return $this->fail('Bu vazifaga fayl biriktirib bo‘lmaydi.', 422);
        }

        try {
            $existing = HomeworkSubmission::where('homework_id', $homework->id)
                ->where('user_id', $me->id)
                ->first();

            $path = $existing?->submission_file;

            if ($request->hasFile('file')) {
                $new = $request->file('file')->store(
                    $upload['directory'] ?? 'homework',
                    $upload['disk'] ?? 'public'
                );

                // Replace: drop the previous upload so storage does not grow
                // by one dead file per re-submission.
                if ($path && Storage::disk($upload['disk'] ?? 'public')->exists($path)) {
                    Storage::disk($upload['disk'] ?? 'public')->delete($path);
                }

                $path = $new;
            }

            $late = $homework->due_date !== null && $homework->due_date->isPast();

            HomeworkSubmission::updateOrCreate(
                ['homework_id' => $homework->id, 'user_id' => $me->id],
                [
                    'submission_text' => $data['submission_text'] ?? null,
                    'submission_file' => $path,
                    'submitted_at'    => now(),
                    // Never let a student write score / comment.
                    'status'          => $late
                        ? HomeworkSubmission::STATUS_LATE
                        : HomeworkSubmission::STATUS_DONE,
                ]
            );

            return $this->ok(
                ['late' => $late],
                $late ? 'Topshirildi (muddatdan kech).' : 'Topshirildi.'
            );
        } catch (\Exception $e) {
            Log::error('StudentApiController@submitHomework error: ' . $e->getMessage());

            return $this->fail('Topshirishda xatolik yuz berdi.', 500);
        }
    }

    public function skills(Request $request)
    {
        $me = $request->user();
        $labels = (array) config('grading.skill_labels');

        $rows = LessonSkillGrade::where('user_id', $me->id)
            ->whereNotNull('score')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['skill', 'score', 'created_at']);

        $averages = $rows->groupBy('skill')->map(fn($g) => (int) round($g->avg('score')));

        return $this->ok([
            'averages' => collect((array) config('grading.skills'))->map(fn($s) => [
                'key'   => $s,
                'label' => $labels[$s] ?? $s,
                'score' => $averages[$s] ?? null,
            ])->values(),
            'recent' => $rows->take(30)->map(fn($r) => [
                'skill' => $labels[$r->skill] ?? $r->skill,
                'score' => $r->score,
                'date'  => $r->created_at?->format('d.m.Y'),
            ])->values(),
        ]);
    }

    public function progress(Request $request, ProgressService $progress)
    {
        $me = $request->user();
        $p = $progress->forStudent($me->id);

        $tests = Assessment::where('assessments.user_id', $me->id)
            ->leftJoin('lesson_and_histories', 'assessments.history_id', '=', 'lesson_and_histories.id')
            ->select('assessments.*', 'lesson_and_histories.name as test_name')
            ->orderByDesc('assessments.created_at')
            ->limit(15)
            ->get();

        return $this->ok([
            'current'    => $p['current'],
            'previous'   => $p['previous'],
            'delta'      => $p['delta'],
            'basis'      => $p['basis'],
            'buckets'    => $p['buckets'],
            'components' => $p['components'],
            'tests'      => $tests->map(fn($t) => [
                'name'  => $t->test_name ?? 'Test',
                'mark'  => $t->get_mark,
                'skill' => $t->skill,
                'for'   => $t->skillLabel(),
                'date'  => $t->created_at?->format('d.m.Y'),
            ]),
        ]);
    }

    public function certificates(Request $request)
    {
        $items = Certificate::where('user_id', $request->user()->id)
            ->with('group:id,name')
            ->orderByDesc('issued_at')
            ->get();

        return $this->ok($items->map(fn(Certificate $c) => [
            'id'          => $c->id,
            'serial'      => $c->serial,
            'title'       => $c->title,
            'level'       => $c->level,
            'final_score' => $c->final_score,
            'group'       => $c->group?->name,
            'issued_at'   => $c->issued_at?->format('d.m.Y'),
            'download'    => route('certificates.download', $c->id),
        ]));
    }
}
