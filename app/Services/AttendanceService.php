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
            
        $studentIds = $students->pluck('id');
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
            ->unique();

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
            for ($i = 1; $i <= 31; $i++) {
                $day = str_pad($i, 2, '0', STR_PAD_LEFT);

                if ($lessonDays->contains($day)) {
                    // A lesson happened on this day, assume PRESENT by default
                    $data[$student->name][$day] = 1; // 1 = Present
                } else {
                    // No lesson on this day
                    $data[$student->name][$day] = ''; // No status
                }
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
            'attendances' => $recentAttendances,
            'group' => $group
        ];
    }
}
