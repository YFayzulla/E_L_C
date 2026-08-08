<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\HomeworkSubmission;
use App\Models\LessonSkillGrade;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * "O‘zlashtirish ko‘rsatkichi" — the per-student mastery trend, blended from
 * attendance, tests, homework and skill grades. See App\Services\ProgressService.
 *
 * Everything here is read-only: no controller in this file writes a row.
 */
class ProgressController extends Controller
{
    use AuthorizesGroupAccess;

    /**
     * Group picker + each group's current average.
     *
     * NOTE: the base Controller declares index() with no parameters (it is also
     * the dashboard controller), so this signature must match. Use request().
     */
    public function index()
    {
        try {
            $groupIds = $this->accessibleGroupIds();

            // NOTE: no column list on with('teachers') — it is a belongsToMany
            // and an unqualified `id` collides with group_teachers.id.
            $groups = Group::whereIn('id', $groupIds)
                ->withCount(['students as members_count'])
                ->with('teachers')
                ->orderBy('name')
                ->get();

            // One pass for every group — forGroup() in a loop would be eight
            // queries per group.
            $progress = $this->progress()->forGroups($groups->pluck('id')->all());

            $values  = collect($progress)->pluck('current')->filter(fn($v) => $v !== null);
            $overall = $values->isEmpty() ? null : (int) round($values->avg());

            return view('progress.index', [
                'groups'   => $groups,
                'progress' => $progress,
                'overall'  => $overall,
                'measured' => $values->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProgressController@index error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'O‘zlashtirish sahifasini ochishda xatolik.');
        }
    }

    /**
     * One group: average trend plus a per-student table sorted by decline.
     */
    public function group(Request $request, int $group)
    {
        $this->assertTeachesGroup($group);

        try {
            $groupModel = Group::with('teachers')->findOrFail($group);

            $progress = $this->progress()->forGroup($group);

            // Steepest decline first; students with no comparable month last.
            $students = collect($progress['students'])
                ->map(fn(array $row, $id) => $row + ['id' => (int) $id])
                ->sortBy(fn(array $row) => $row['delta'] === null ? PHP_INT_MAX : $row['delta'])
                ->values();

            $declining = $students->filter(fn(array $r) => $r['delta'] !== null && $r['delta'] < 0)->count();
            $atRisk    = $students->filter(
                fn(array $r) => $r['current'] !== null && $r['current'] < config('grading.bands.ok', 60)
            )->count();

            return view('progress.group', [
                'group'     => $groupModel,
                'progress'  => $progress,
                'students'  => $students,
                'declining' => $declining,
                'atRisk'    => $atRisk,
            ]);
        } catch (\Exception $e) {
            Log::error('ProgressController@group error: ' . $e->getMessage());

            return redirect()->route('progress.index')->with('error', 'Guruh dinamikasini ochishda xatolik.');
        }
    }

    /**
     * One student: the trend, the component breakdown and recent evidence.
     */
    public function student(Request $request, int $student)
    {
        $this->assertTeachesStudent($student);

        try {
            $studentModel = User::inCurrentCentre()->with('groups')->findOrFail($student);

            $progress = $this->progress()->forStudent($student);

            // The first of this student's groups the CALLER may open. Linking to
            // $student->groups->first() would 403 a teacher whenever the student
            // also sits in a colleague's group.
            $groupId = $this->accessibleGroupId($studentModel);

            return view('progress.student', [
                'student'       => $studentModel,
                'progress'      => $progress,
                'evidence'      => $this->evidence($student),
                'reportGroupId' => $groupId,
                'backUrl'       => $groupId
                    ? route('progress.group', $groupId)
                    : route('progress.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('ProgressController@student error: ' . $e->getMessage());

            return redirect()->route('progress.index')->with('error', 'Talaba dinamikasini ochishda xatolik.');
        }
    }

    /**
     * The student's own progress page — read-only, nobody else visible.
     */
    public function mine(Request $request)
    {
        try {
            $user = auth()->user();

            $progress = $this->progress()->forStudent($user->id);

            return view('student.progress', [
                'student'  => $user,
                'progress' => $progress,
                'evidence' => $this->evidence($user->id),
            ]);
        } catch (\Exception $e) {
            Log::error('ProgressController@mine error: ' . $e->getMessage());

            return redirect()->route('dashboard')->with('error', 'O‘zlashtirish sahifangizni ochishda xatolik.');
        }
    }

    /* ------------------------------------------------------------------ */

    private function progress(): ProgressService
    {
        return app(ProgressService::class);
    }

    /**
     * The rows behind the number: recent tests, homework, skill marks and
     * missed lessons.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function evidence(int $userId): array
    {
        return [
            'tests' => Assessment::leftJoin(
                'lesson_and_histories',
                'assessments.history_id',
                '=',
                'lesson_and_histories.id'
            )
                ->where('assessments.user_id', $userId)
                ->orderByDesc('assessments.created_at')
                ->limit(6)
                ->get(['assessments.*', 'lesson_and_histories.name as test_name']),

            'homework' => HomeworkSubmission::with('homework:id,title,max_score,due_date')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),

            'skills' => LessonSkillGrade::with('lesson:id,name')
                ->where('user_id', $userId)
                ->whereNotNull('score')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(8)
                ->get(),

            'absences' => Attendance::with(['group:id,name', 'lesson:id,name'])
                ->where('user_id', $userId)
                ->whereIn('status', [0, 2])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * The first of this student's groups the signed-in user is allowed to open,
     * or null. Drives both the back link and the skills-report link, so neither
     * can point at a group that will 403.
     */
    private function accessibleGroupId(User $student): ?int
    {
        $accessible = $this->accessibleGroupIds();

        $groupId = $student->groups
            ->pluck('id')
            ->first(fn($id) => $accessible->contains($id));

        return $groupId === null ? null : (int) $groupId;
    }
}
