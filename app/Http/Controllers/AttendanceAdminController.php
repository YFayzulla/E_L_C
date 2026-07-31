<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The attendance surface the admin never had: one screen listing every group's
 * attendance for a chosen month, with a way through to each group's grid, plus
 * a flat log of every absence and late arrival.
 *
 * Everything here is aggregate SQL — never attendanceRate() in a loop.
 */
class AttendanceAdminController extends Controller
{
    use AuthorizesGroupAccess;

    /** Cross-group summary for ?date=Y-m, filterable by group and teacher. */
    public function overview(Request $request)
    {
        try {
            $requested = $request->query('date');
            $date = is_string($requested) && preg_match('/^\d{4}-\d{2}$/', $requested)
                ? $requested
                : now()->format('Y-m');

            [$year, $month] = array_map('intval', explode('-', $date));

            $accessibleIds = $this->accessibleGroupIds();

            $groupId = (int) $request->query('group_id');
            $teacherId = (int) $request->query('teacher_id');

            $query = Group::query()
                ->whereIn('id', $accessibleIds)
                ->with('teachers:id,name')
                // getStudentsCountAttribute() shadows withCount('students') — alias it.
                ->withCount(['students as members_count'])
                ->orderBy('name');

            if ($groupId > 0) {
                $query->where('id', $groupId);
            }

            if ($teacherId > 0) {
                $query->whereHas('teachers', fn($q) => $q->where('users.id', $teacherId));
            }

            $groups = $query->get();
            $groupIds = $groups->pluck('id');

            // Lessons actually held this month, one entry per group.
            // `group` is a reserved word in MySQL, hence the backticks.
            $lessonCounts = LessonAndHistory::whereIn('group', $groupIds)
                ->where('data', 1)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw('`group` as group_key, COUNT(DISTINCT DATE(created_at)) as lessons_total')
                ->groupBy('group')
                ->pluck('lessons_total', 'group_key');

            // Absences and lates, one row per group. Present is implicit.
            $marks = Attendance::whereIn('group_id', $groupIds)
                ->whereIn('status', [0, 2])
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->selectRaw('group_id')
                ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent_total')
                ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as late_total')
                ->groupBy('group_id')
                ->get()
                ->keyBy('group_id');

            $rows = [];
            $totalLessons = 0;
            $totalAbsent = 0;
            $totalLate = 0;
            $totalSlots = 0;

            foreach ($groups as $group) {
                $lessons = (int) ($lessonCounts[$group->id] ?? 0);
                $absent = (int) ($marks[$group->id]->absent_total ?? 0);
                $late = (int) ($marks[$group->id]->late_total ?? 0);
                $members = (int) $group->members_count;
                $slots = $lessons * $members;

                $rows[] = [
                    'group' => $group,
                    'teachers' => $group->teachers->pluck('name')->implode(', '),
                    'members' => $members,
                    'lessons' => $lessons,
                    'absent' => $absent,
                    'late' => $late,
                    'rate' => $slots > 0 ? (int) round((($slots - $absent) / $slots) * 100) : null,
                ];

                $totalLessons += $lessons;
                $totalAbsent += $absent;
                $totalLate += $late;
                $totalSlots += $slots;
            }

            return view('admin.attendance.index', [
                'rows' => $rows,
                'date' => $date,
                'year' => $year,
                'month' => $month,
                'groupId' => $groupId,
                'teacherId' => $teacherId,
                'groupOptions' => Group::whereIn('id', $accessibleIds)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'teacherOptions' => User::role('user')->orderBy('name')->get(['id', 'name']),
                'totalLessons' => $totalLessons,
                'totalAbsent' => $totalAbsent,
                'totalLate' => $totalLate,
                'averageRate' => $totalSlots > 0
                    ? (int) round((($totalSlots - $totalAbsent) / $totalSlots) * 100)
                    : null,
            ]);
        } catch (\Exception $e) {
            Log::error('AttendanceAdminController@overview error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Davomat hisobotini yuklashda xatolik yuz berdi.');
        }
    }

    /** Flat, filterable log of every absence and late arrival. */
    public function log(Request $request)
    {
        try {
            $accessibleIds = $this->accessibleGroupIds();

            $from = $this->parseDay($request->query('from')) ?? now()->startOfMonth();
            $to = $this->parseDay($request->query('to')) ?? now()->endOfMonth();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }

            $groupId = (int) $request->query('group_id');
            $status = $request->query('status');
            $status = in_array($status, ['0', '2'], true) ? (int) $status : null;

            $base = Attendance::query()
                ->whereIn('status', [0, 2])
                ->whereIn('group_id', $accessibleIds)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

            if ($groupId > 0) {
                $base->where('group_id', $groupId);
            }

            if ($status !== null) {
                $base->where('status', $status);
            }

            $totals = (clone $base)
                ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent_total')
                ->selectRaw('SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as late_total')
                ->first();

            $records = $base
                ->with(['user:id,name', 'group:id,name', 'teacher:id,name', 'lesson:id,name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->appends($request->only(['group_id', 'from', 'to', 'status']));

            return view('admin.attendance.log', [
                'records' => $records,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'groupId' => $groupId,
                'status' => $status,
                'groupOptions' => Group::whereIn('id', $accessibleIds)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'absentTotal' => (int) ($totals->absent_total ?? 0),
                'lateTotal' => (int) ($totals->late_total ?? 0),
            ]);
        } catch (\Exception $e) {
            Log::error('AttendanceAdminController@log error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Davomat jurnalini yuklashda xatolik yuz berdi.');
        }
    }

    /** Strict Y-m-d only; anything else falls back to the caller's default. */
    private function parseDay($value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }
}
