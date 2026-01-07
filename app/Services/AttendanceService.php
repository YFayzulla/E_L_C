<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Group;
use App\Models\LessonAndHistory;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public function attendance($id)
    {
        $group = Group::findOrFail($id);
        $date = request('date', now()->format('Y-m'));
        list($year, $month) = explode('-', $date);

        // 1. Get all students in the group (UPDATED for Many-to-Many)
        $students = User::role('student')
            ->whereHas('groups', function ($query) use ($group) {
                $query->where('groups.id', $group->id);
            })
            ->get();
            
        $studentNames = $students->pluck('name', 'id');

        // 2. Find all days in the month where a lesson was recorded for this group
        $lessonDays = LessonAndHistory::where('group', $id)
            ->where('data', 1) // 1 = Attendance lesson
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(function ($lesson) {
                return $lesson->created_at->format('d');
            })
            ->unique()
            ->sort()
            ->values();

        // 3. Get all ABSENT and LATE records for the month
        $absentLateRecords = Attendance::where('group_id', $id)
            ->whereIn('status', [0, 2]) // 0 = Absent, 2 = Late
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        // 4. Build the data grid
        $data = [];
        foreach ($students as $student) {
            $data[$student->name] = [];
            // Only create columns for days where lessons happened
            foreach ($lessonDays as $day) {
                $data[$student->name][$day] = 1; // default Present
            }
        }

        // 5. Overlay the absent/late records onto the grid
        foreach ($absentLateRecords as $record) {
            $day = $record->created_at->format('d');
            $studentName = $studentNames[$record->user_id] ?? null;
            if ($studentName) {
                $data[$studentName][$day] = $record->status;
            }
        }

        // 6. Get recent attendance records for the bottom table (paginated)
        $recentAttendances = Attendance::where('group_id', $id)
            ->with(['user', 'teacher', 'lesson'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return [
            'students' => $students,
            'today' => now()->day,
            'data' => $data,
            'year' => $year,
            'month' => $month,
            'lessonDays' => $lessonDays->toArray(),
            'attendances' => $recentAttendances,
            'group' => $group,
//            'selected_month' =>
        ];
    }
}
