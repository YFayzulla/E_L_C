<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Http\Requests\SkillGrade\StoreRequest;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\LessonSkillGrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Per-skill marks (reading / listening / writing / speaking) recorded during a
 * lesson.
 *
 * The grades always hang off an EXISTING lesson_and_histories row with
 * data = 1. This controller never creates one: a lesson row is the denominator
 * of every attendance percentage in the app, so inventing one here would
 * silently drop everybody's davomat.
 */
class LessonSkillGradeController extends Controller
{
    use AuthorizesGroupAccess;

    /** How far back the lesson picker looks. */
    private const LESSON_WINDOW_DAYS = 60;

    /**
     * Group picker for the teacher.
     */
    public function groups(Request $request)
    {
        try {
            $groupIds = $this->accessibleGroupIds();

            $groups = Group::whereIn('id', $groupIds)
                ->withCount(['students as members_count'])
                ->with('room')
                ->orderBy('name')
                ->get();

            // Two grouped aggregates instead of a query per card.
            $lessonStats = LessonAndHistory::whereIn('group', $groupIds)
                ->where('data', 1)
                ->select('group', DB::raw('COUNT(*) as lessons_count'), DB::raw('MAX(created_at) as last_lesson_at'))
                ->groupBy('group')
                ->get()
                ->keyBy('group');

            // NOTE: ->get()->pluck(), not ->pluck() — the query builder's pluck
            // rewrites the SELECT list and would drop the COUNT() alias.
            $gradeCounts = LessonSkillGrade::whereIn('group_id', $groupIds)
                ->select('group_id', DB::raw('COUNT(*) as grades_count'))
                ->groupBy('group_id')
                ->get()
                ->pluck('grades_count', 'group_id');

            return view('teacher.skills.index', compact('groups', 'lessonStats', 'gradeCounts'));
        } catch (\Exception $e) {
            Log::error('LessonSkillGradeController@groups error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Ko‘nikma baholari sahifasini ochishda xatolik.');
        }
    }

    /**
     * Students x skills matrix for one existing lesson.
     */
    public function grade(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        try {
            $groupModel = Group::findOrFail($group);

            $lessons = LessonAndHistory::where('group', $group)
                ->where('data', 1)
                ->where('created_at', '>=', now()->subDays(self::LESSON_WINDOW_DAYS))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'name', 'created_at']);

            $students = $this->roster($group);

            $lessonId = (int) $request->query('lesson', 0);

            if ($lessonId && ! $lessons->contains('id', $lessonId)) {
                $lessonId = 0;
            }

            if (! $lessonId) {
                $lessonId = (int) (optional($lessons->first())->id ?? 0);
            }

            // [user id][skill] => score, plus one comment per student.
            $scores   = [];
            $comments = [];

            if ($lessonId) {
                foreach (LessonSkillGrade::where('lesson_id', $lessonId)->get() as $row) {
                    $scores[(int) $row->user_id][$row->skill] = $row->score;

                    if (filled($row->comment)) {
                        $comments[(int) $row->user_id] = $row->comment;
                    }
                }
            }

            return view('teacher.skills.grade', [
                'group'    => $groupModel,
                'lessons'  => $lessons,
                'students' => $students,
                'lessonId' => $lessonId,
                'scores'   => $scores,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            Log::error('LessonSkillGradeController@grade error: ' . $e->getMessage());

            return redirect()->route('skills.groups')->with('error', 'Baholash sahifasini ochishda xatolik.');
        }
    }

    /**
     * Upsert the matrix. A blank score means "not assessed" — the row is
     * skipped entirely and never stored as a zero.
     */
    public function store(StoreRequest $request, int $group)
    {
        $this->assertTeachesGroup($group);

        $lessonId = (int) $request->input('lesson_id');

        // The lesson must belong to THIS group, otherwise a teacher could graft
        // marks onto another group's lesson just by editing the hidden field.
        $lessonBelongs = LessonAndHistory::where('id', $lessonId)
            ->where('group', $group)
            ->where('data', 1)
            ->exists();

        if (! $lessonBelongs) {
            return redirect()->back()->withInput()
                ->with('error', 'Tanlangan dars bu guruhga tegishli emas.');
        }

        $roster   = $this->roster($group)->pluck('id')->flip();
        $skills   = (array) config('grading.skills', []);
        $comments = (array) $request->input('comment', []);
        $graderId = auth()->id();
        $now      = now();

        $rows = [];

        foreach ((array) $request->input('score', []) as $userId => $bySkill) {
            $userId = (int) $userId;

            if (! $roster->has($userId) || ! is_array($bySkill)) {
                continue;
            }

            $comment = trim((string) ($comments[$userId] ?? ''));
            $comment = $comment === '' ? null : Str::limit($comment, 250, '');

            foreach ($bySkill as $skill => $score) {
                if (! in_array($skill, $skills, true)) {
                    continue;
                }

                // Blank = baholanmagan. Never written, never zero.
                if ($score === null || $score === '' || ! is_scalar($score)) {
                    continue;
                }

                $rows[] = [
                    'lesson_id'  => $lessonId,
                    'group_id'   => $group,
                    'user_id'    => $userId,
                    'skill'      => $skill,
                    'score'      => max(0, min(100, (int) $score)),
                    'comment'    => $comment,
                    'graded_by'  => $graderId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (empty($rows)) {
            return redirect()->back()->withInput()
                ->with('warning', 'Hech qanday baho kiritilmadi.');
        }

        DB::beginTransaction();

        try {
            foreach (array_chunk($rows, 200) as $chunk) {
                LessonSkillGrade::upsert(
                    $chunk,
                    ['lesson_id', 'user_id', 'skill'],
                    ['score', 'comment', 'graded_by', 'updated_at']
                );
            }

            DB::commit();

            return redirect()->route('skills.grade', ['group' => $group, 'lesson' => $lessonId])
                ->with('success', count($rows) . ' ta baho saqlandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LessonSkillGradeController@store error: ' . $e->getMessage());

            return redirect()->back()->withInput()
                ->with('error', 'Baholarni saqlashda tizim xatoligi yuz berdi.');
        }
    }

    /**
     * Read-only skill report for a group (teacher or admin).
     */
    public function report(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        try {
            $groupModel = Group::findOrFail($group);

            $from = $this->parseDate($request->query('from'), now()->subDays(90))->startOfDay();
            $to   = $this->parseDate($request->query('to'), now())->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            $students = $this->roster($group);

            $rows = LessonSkillGrade::where('group_id', $group)
                ->whereNotNull('score')
                ->whereBetween('created_at', [$from, $to])
                ->select('user_id', 'skill', DB::raw('AVG(score) as avg_score'), DB::raw('COUNT(*) as marks'))
                ->groupBy('user_id', 'skill')
                ->get();

            $skills = (array) config('grading.skills', []);

            // [user id][skill] => ['avg' => int, 'marks' => int]
            $matrix = [];

            foreach ($rows as $row) {
                $matrix[(int) $row->user_id][$row->skill] = [
                    'avg'   => (int) round((float) $row->avg_score),
                    'marks' => (int) $row->marks,
                ];
            }

            // Per-student overall + the group average of the student averages,
            // so one very active student cannot dominate the summary row.
            $overall     = [];
            $groupTotals = array_fill_keys($skills, ['sum' => 0, 'n' => 0]);

            foreach ($students as $student) {
                $values = [];

                foreach ($skills as $skill) {
                    if (! isset($matrix[$student->id][$skill])) {
                        continue;
                    }

                    $value    = $matrix[$student->id][$skill]['avg'];
                    $values[] = $value;

                    $groupTotals[$skill]['sum'] += $value;
                    $groupTotals[$skill]['n']++;
                }

                $overall[$student->id] = $values === [] ? null : (int) round(array_sum($values) / count($values));
            }

            $groupAverages = [];
            foreach ($skills as $skill) {
                $groupAverages[$skill] = $groupTotals[$skill]['n'] > 0
                    ? (int) round($groupTotals[$skill]['sum'] / $groupTotals[$skill]['n'])
                    : null;
            }

            $present            = array_filter($groupAverages, fn($v) => $v !== null);
            $groupOverall       = $present === [] ? null : (int) round(array_sum($present) / count($present));
            $totalMarks         = (int) $rows->sum('marks');

            return view('teacher.skills.report', [
                'group'         => $groupModel,
                'students'      => $students,
                'skills'        => $skills,
                'matrix'        => $matrix,
                'overall'       => $overall,
                'groupAverages' => $groupAverages,
                'groupOverall'  => $groupOverall,
                'totalMarks'    => $totalMarks,
                'from'          => $from,
                'to'            => $to,
            ]);
        } catch (\Exception $e) {
            Log::error('LessonSkillGradeController@report error: ' . $e->getMessage());

            // A fixed target, not back() — the referer is often this same URL,
            // which would bounce the user round a redirect loop.
            return redirect()->route('progress.index')->with('error', 'Hisobotni ochishda xatolik.');
        }
    }

    /**
     * The student's own skill breakdown.
     */
    public function studentIndex(Request $request)
    {
        try {
            $user   = auth()->user();
            $skills = (array) config('grading.skills', []);

            $rows = LessonSkillGrade::where('user_id', $user->id)
                ->whereNotNull('score')
                ->select('skill', DB::raw('AVG(score) as avg_score'), DB::raw('COUNT(*) as marks'))
                ->groupBy('skill')
                ->get();

            $averages = [];
            foreach ($skills as $skill) {
                $averages[$skill] = ['avg' => null, 'marks' => 0];
            }

            foreach ($rows as $row) {
                $averages[$row->skill] = [
                    'avg'   => (int) round((float) $row->avg_score),
                    'marks' => (int) $row->marks,
                ];
            }

            $recent = LessonSkillGrade::where('user_id', $user->id)
                ->with(['lesson:id,name,created_at', 'group:id,name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20);

            $present = array_filter(array_column($averages, 'avg'), fn($v) => $v !== null);
            $overall = $present === [] ? null : (int) round(array_sum($present) / count($present));

            return view('student.skills', compact('averages', 'recent', 'skills', 'overall'));
        } catch (\Exception $e) {
            Log::error('LessonSkillGradeController@studentIndex error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Ko‘nikma baholarini ochishda xatolik.');
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Students currently enrolled in the group, alphabetically.
     */
    private function roster(int $group)
    {
        return User::role('student')
            ->whereHas('groups', fn($q) => $q->where('groups.id', $group))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.photo']);
    }

    /**
     * A date from the query string, falling back to a default instead of
     * throwing when somebody hand-edits the URL.
     */
    private function parseDate($value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return $fallback->copy();
        }
    }
}
