<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;

class AttendanceService
{
    /**
     * Build the monthly attendance matrix for one group.
     *
     * The returned array is consumed by TeacherAdminPanel@attendance and
     * GroupExtraController@attendance — both spell out every key, so any change
     * here has to be mirrored in both controllers.
     *
     * @return array<string, mixed>
     */
    public function attendance($id): array
    {
        $group = Group::findOrFail($id);

        // A blank ?date= (the old "Select Month" option) used to explode into ['']
        // and fatal on list($year, $month). Anything that is not exactly Y-m —
        // including an array-shaped query string — falls back to the current month.
        $requested = request('date');
        $date = is_string($requested) && preg_match('/^\d{4}-\d{2}$/', $requested)
            ? $requested
            : now()->format('Y-m');

        [$year, $month] = array_map('intval', explode('-', $date));

        // 1. Students of the group.
        $students = User::role('student')
            ->whereHas('groups', fn($query) => $query->where('groups.id', $group->id))
            ->orderBy('name')
            ->get();

        // id => name. Names are NOT unique, so the grid is keyed by id and the
        // blade looks the label up here.
        $studentNames = $students->pluck('name', 'id');

        // 2. Days of the month on which a lesson was actually recorded.
        //    Kept as int so the screen and AttendanceExport agree on the key.
        $lessonDays = LessonAndHistory::where('group', $group->id)
            ->where('data', 1)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(fn($lesson) => (int) $lesson->created_at->format('d'))
            ->unique()
            ->sort()
            ->values();

        // 3. Absences and lates. Present is implicit — no row is ever written for it.
        $absentLateRecords = Attendance::where('group_id', $group->id)
            ->whereIn('status', [0, 2])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        // 4. Grid, keyed by USER ID (two students may share a name).
        $data = [];
        foreach ($students as $student) {
            $data[$student->id] = [];
            foreach ($lessonDays as $day) {
                $data[$student->id][$day] = 1; // present by default
            }
        }

        // 5. Overlay the absences / lates.
        $absentCount = 0;
        $lateCount = 0;
        foreach ($absentLateRecords as $record) {
            $day = (int) $record->created_at->format('d');
            $status = (int) $record->status;

            if (isset($data[$record->user_id])) {
                $data[$record->user_id][$day] = $status;
            }

            $status === 2 ? $lateCount++ : $absentCount++;
        }

        // 6. Bottom table — this month only, newest first.
        $recentAttendances = Attendance::where('group_id', $group->id)
            ->whereIn('status', [0, 2])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->with(['user:id,name', 'teacher:id,name', 'lesson:id,name'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(request()->only('date'));

        $slots = $lessonDays->count() * $students->count();

        return [
            'students' => $students,
            'studentNames' => $studentNames,
            'today' => now()->day,
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'date' => $date,
            'lessonDays' => $lessonDays->toArray(),
            'attendances' => $recentAttendances,
            'group' => $group,
            'absentCount' => $absentCount,
            'lateCount' => $lateCount,
            'rate' => $slots > 0 ? (int) round((($slots - $absentCount) / $slots) * 100) : null,
        ];
    }
}
