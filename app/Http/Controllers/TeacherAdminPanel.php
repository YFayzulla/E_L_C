<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Attendance;
use App\Models\Group;
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
    use AuthorizesGroupAccess;

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
        $this->assertTeachesGroup((int) $id);

        try {
            $serviceData = $this->serviceAttendance->attendance($id);

            return view('teacher.attendance.attendance', [
                'id' => $id,
                'today' => $serviceData['today'],
                'data' => $serviceData['data'],
                'year' => $serviceData['year'],
                'month' => $serviceData['month'],
                'date' => $serviceData['date'],
                'lessonDays' => $serviceData['lessonDays'],
                'attendances' => $serviceData['attendances'],
                'group' => $serviceData['group'],
                'students' => $serviceData['students'],
                'studentNames' => $serviceData['studentNames'],
                'absentCount' => $serviceData['absentCount'],
                'lateCount' => $serviceData['lateCount'],
                'rate' => $serviceData['rate'],
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
        $this->assertTeachesGroup((int) $id);

        $request->validate([
            'lesson' => 'nullable|string|max:255',
            'status' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $group = Group::findOrFail($id);

            // Only real members of THIS group may be marked. A crafted POST that
            // carries a stranger's id is silently ignored.
            $memberIds = User::role('student')
                ->whereHas('groups', fn($q) => $q->where('groups.id', $group->id))
                ->pluck('id')
                ->map(fn($v) => (int) $v)
                ->all();

            $customName = $request->filled('lesson') ? trim((string) $request->input('lesson')) : null;

            // One lesson per group per day. Re-submitting corrects the existing
            // lesson instead of inflating the denominator of every attendance %.
            $lesson = LessonAndHistory::where('group', $group->id)
                ->where('data', 1)
                ->whereDate('created_at', today())
                ->first();

            if (! $lesson) {
                $lesson = LessonAndHistory::create([
                    'name' => $customName ?: 'Dars: ' . now()->format('d.m.Y'),
                    'data' => 1,
                    'group' => $group->id,
                ]);
            } elseif ($customName && $customName !== $lesson->name) {
                $lesson->update(['name' => $customName]);
            }

            $checkerId = auth()->id();
            $absent = 0;
            $late = 0;

            foreach ($request->input('status', []) as $userId => $statusValue) {
                if (! is_numeric($userId)) {
                    continue;
                }

                $userId = (int) $userId;

                if (! in_array($userId, $memberIds, true)) {
                    continue;
                }

                $status = (int) $statusValue;

                // Present is implicit: drop any stale absent/late row so a
                // mis-click can actually be undone from the UI.
                if ($status === 1) {
                    Attendance::where('user_id', $userId)
                        ->where('lesson_id', $lesson->id)
                        ->delete();
                    continue;
                }

                if (! in_array($status, [0, 2], true)) {
                    continue;
                }

                Attendance::updateOrCreate(
                    ['user_id' => $userId, 'lesson_id' => $lesson->id],
                    [
                        'group_id' => $group->id,
                        'who_checked' => $checkerId,
                        'status' => $status,
                    ]
                );

                $status === 2 ? $late++ : $absent++;
            }

            DB::commit();

            return redirect()->back()->with(
                'success',
                "Davomat saqlandi. Kelmadi: {$absent}, kechikdi: {$late}."
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherAdminPanel@attendance_submit error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Davomatni saqlashda xatolik yuz berdi.');
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
                // Students only ever see their own absences and lates —
                // "keldi" is implicit and has no row.
                $attendances = Attendance::where('user_id', $user->id)
                    ->whereIn('status', [0, 2])
                    ->with(['lesson:id,name', 'group:id,name'])
                    ->orderByDesc('created_at')
                    ->paginate(20);

                return view('student.attendance', [
                    'attendances' => $attendances,
                    'absentCount' => Attendance::where('user_id', $user->id)->where('status', 0)->count(),
                    'lateCount' => Attendance::where('user_id', $user->id)->where('status', 2)->count(),
                    'rate' => $user->attendanceRate(),
                ]);
            }

            // Default behavior for teachers
            return view('teacher.attendance.index', [
                'groups' => $this->attendanceGroupList(),
            ]);
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@attendanceIndex error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ma\'lumotlarni yuklashda xatolik.');
        }
    }

    /**
     * Guruhlar ro'yxati (davomat uchun): a'zolar soni, o'tilgan darslar,
     * oxirgi dars sanasi. Admin hamma guruhni ko'radi.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Group>
     */
    private function attendanceGroupList()
    {
        return Group::query()
            ->whereIn('id', $this->accessibleGroupIds())
            // getStudentsCountAttribute() shadows withCount('students') — alias it.
            ->withCount(['students as members_count'])
            ->withCount(['lessons as lessons_total'])
            ->withMax('lessons as last_lesson_at', 'created_at')
            ->orderBy('name')
            ->get();
    }

    public function groups()
    {
        $teacherId = Auth::id();
        $groups = GroupTeacher::where('teacher_id', $teacherId)->with('group')->get();
        return view('teacher.group.index', compact('groups'));
    }

    public function attendanceGroups()
    {
        try {
            return view('teacher.attendance.index', [
                'groups' => $this->attendanceGroupList(),
            ]);
        } catch (\Exception $e) {
            Log::error('TeacherAdminPanel@attendanceGroups error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlar ro\'yxatini yuklashda xatolik.');
        }
    }

    public function assessmentGroups()
    {
        $teacherId = Auth::id();
        $groups = GroupTeacher::where('teacher_id', $teacherId)->with('group')->get();
        return view('teacher.assessment.index', compact('groups'));
    }

    public function studentComment(Request $request, $id)
    {
        // `role:user` only proves the caller is a teacher. Without this a teacher
        // could POST any student id and append text to that student's description.
        $this->assertTeachesStudent((int) $id);

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
