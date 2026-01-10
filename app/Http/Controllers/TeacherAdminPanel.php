<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherAdminPanel extends Controller
{
    public function __construct(protected AttendanceService $serviceAttendance)
    {
        // Service avtomatik inject qilinadi
    }

    /**
     * O'qituvchiga biriktirilgan guruhlar ro'yxati.
     */
    public function group()
    {
        try {
            $teacherId = auth()->id();
            $groups = GroupTeacher::where('teacher_id', $teacherId)
                ->with('group')
                ->get();
            return view('teacher.group', compact('groups'));
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@group error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlarni yuklashda xatolik yuz berdi.');
        }
    }

    /**
     * Davomat qilish sahifasi (Service orqali ma'lumot oladi).
     */
    public function attendance($id)
    {
        try {
            $serviceData = $this->serviceAttendance->attendance($id);
            return view('teacher.attendance.attendance', [
                'id' => $id,
                'today' => $serviceData['today'] ?? now(),
                'data' => $serviceData['data'] ?? [],
                'year' => $serviceData['year'] ?? date('Y'),
                'month' => $serviceData['month'] ?? date('m'),
                'lessonDays' => $serviceData['lessonDays'] ?? [],
                'attendances' => $serviceData['attendances'] ?? [],
                'group' => $serviceData['group'] ?? null,
                'students' => $serviceData['students'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@attendance error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Davomat sahifasini ochishda xatolik.');
        }
    }

    /**
     * Davomatni saqlash (Submit).
     */
    public function attendance_submit(Request $request, $id)
    {
        $request->validate([
            'lesson' => 'nullable|string|max:255', // Made nullable
            'status' => 'required|array',
        ]);

        $statuses = $request->input('status', []);

        DB::beginTransaction();
        try {
            // Auto-generate lesson name if not provided
            $lessonName = $request->lesson ?? 'Lesson: ' . now()->format('d M Y');

            $lesson = LessonAndHistory::create([
                'name' => $lessonName,
                'data' => 1,
                'group' => $id,
            ]);

            $attendancesToInsert = [];
            $checkerId = auth()->id();
            $now = now();

            foreach ($statuses as $userId => $statusValue) {
                // ONLY save if status is NOT 'Present' (1)
                // 0 = Absent, 2 = Late. We skip 1.
                if ((int)$statusValue !== 1) {
                    if (!is_numeric($userId)) continue;

                    $attendancesToInsert[] = [
                        'user_id' => $userId,
                        'group_id' => $id,
                        'who_checked' => $checkerId,
                        'status' => (int)$statusValue, // Will be 0 (Absent) or 2 (Late)
                        'lesson_id' => $lesson->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($attendancesToInsert)) {
                Attendance::insert($attendancesToInsert);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Davomat muvaffaqiyatli saqlandi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherAdminPanel@attendance_submit error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Saqlashda tizim xatoligi yuz berdi.');
        }
    }

    /**
     * Davomat bo'limi bosh sahifasi.
     */
    public function attendanceIndex()
    {
        try {
            $user = Auth::user();

            if ($user->hasRole('student')) {
                // Students now only see their absences and lates.
                $attendances = Attendance::where('user_id', $user->id)
                    ->whereIn('status', [0, 2]) // Absent or Late
                    ->with(['lesson', 'group'])
                    ->orderByDesc('created_at')
                    ->paginate(20);
                return view('student.attendance', compact('attendances'));
            }

            // Default behavior for teachers
            $groups = GroupTeacher::where('teacher_id', $user->id)
                ->with('group')
                ->get();
            return view('teacher.attendance.index', compact('groups'));
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@attendanceIndex error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ma\'lumotlarni yuklashda xatolik.');
        }
    }

    public function groups()
    {
        $teacherId = Auth::id();
        $groups = GroupTeacher::where('teacher_id', $teacherId)->with('group')->get();
        return view('teacher.group.index', compact('groups'));
    }

    public function attendanceGroups()
    {
        $teacherId = Auth::id();
        $groups = GroupTeacher::where('teacher_id', $teacherId)->with('group')->get();
        return view('teacher.attendance.index', compact('groups'));
    }

    public function assessmentGroups()
    {
        $teacherId = Auth::id();
        $groups = GroupTeacher::where('teacher_id', $teacherId)->with('group')->get();
        return view('teacher.assessment.index', compact('groups'));
    }

    public function studentComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string',
            'group_name' => 'required|string',
        ]);

        try {
            $student = User::findOrFail($id);
            $teacherName = auth()->user()->name;
            $groupName = $request->group_name;
            
            // Append new comment to existing description with teacher name and group name
            $newComment = "\n" . now()->format('d M Y') . " ({$teacherName} - {$groupName}): " . $request->comment;
            $student->description .= $newComment;
            $student->save();

            return redirect()->back()->with('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@studentComment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to add comment.');
        }
    }
}
