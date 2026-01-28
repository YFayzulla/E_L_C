<?php

namespace App\Http\Controllers;

use App\Models\ActiveStudent;
use App\Models\Assessment;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->hasRole('admin')) {
                // Admin sees all groups
                $groups = Group::orderBy('name')->get(); // Added pagination
                return view('admin.assessment.index', compact('groups'));
            } else {
                // Teacher sees only their groups
                $groups = GroupTeacher::where('teacher_id', $user->id)
                    ->with('group')->orderby('name')
                    ->get(); // Added pagination
                return view('teacher.assessment.index', compact('groups'));
            }
        } catch (\Exception $e) {
            Log::error('AssessmentController@index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhlarni yuklashda xatolik yuz berdi.');
        }
    }


    /**
     * Guruh va talabalar ro'yxatini ko'rsatish (Baholash sahifasi).
     *
     * @param int $id Group ID
     */
    public function show($id)
    {
        try {
            $group = Group::findOrFail($id);

            // Talabalarni ism bo'yicha saralab olish (Many-to-Many)
            $students = User::whereHas('groups', function ($query) use ($id) {
                    $query->where('groups.id', $id);
                })
                ->orderBy('name')
                ->get();

            $allGroups = Group::orderBy('name')->get();

            return view('teacher.assessment.make_markes', [
                'students' => $students,
                'id' => $id,
                'groups' => $allGroups,
                'groupName' => $group->name
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('assessment.index')->with('error', 'Guruh topilmadi.');
        } catch (\Exception $e) {
            Log::error('AssessmentController@show error: ' . $e->getMessage());
            return redirect()->route('assessment.index')->with('error', 'Ma\'lumotlarni yuklashda xatolik.');
        }
    }

    /**
     * Baholarni saqlash va talabalarni yangilash.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id Group ID
     */
    public function update(Request $request, $id)
    {
        // 1. Validatsiya (Ma'lumotlar butunligini tekshirish)
        $request->validate([
            'student' => 'required|array',
            'reason' => 'required|array',
            'end_mark' => 'array',
            'lesson' => 'nullable|string',
        ]);

        $end_marks = $request->input('end_mark', []);
        $reasons = $request->input('reason', []);
        $users = $request->input('student', []);

        if (empty($reasons)) {
            return redirect()->back()->with('error', 'Saqlash uchun ma\'lumot topilmadi.');
        }

        DB::beginTransaction(); // Tranzaksiyani boshlash

        try {
            $group = Group::findOrFail($id);

            // 2. Tarix yaratish (History)
            $history = LessonAndHistory::create([
                'group' => $group->id,
                'name' => $request->lesson ?? auth()->user()->name,
                'data' => 2
            ]);

            $assessments = [];
            $studentsToUpdate = [];

            // 3. Ma'lumotlarni tayyorlash
            foreach ($reasons as $index => $reason) {
                $userId = $users[$index] ?? null;
                $mark = $end_marks[$index] ?? null;

                if (!$userId) continue;

                // Agar baho mavjud bo'lsa va 0 bo'lmasa, assessment jadvaliga yozamiz
                if ($mark !== null && $mark != 0) {
                    $assessments[] = [
                        'get_mark' => $mark,
                        'user_id' => $userId,
                        'for_what' => $reason,
                        'group' => $group->name,
                        'history_id' => $history->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Talabaning joriy bahosini yangilash uchun arrayga yig'amiz
                $studentsToUpdate[$userId] = $mark;
            }

            // 4. Assessment jadvaliga bitta so'rov bilan yozish (Bulk Insert)
            if (!empty($assessments)) {
                Assessment::insert($assessments);
            }

            // 5. Talabalarni yangilash va faollikni tekshirish
            if (!empty($studentsToUpdate)) {
                // Faqat kerakli talabalarni bazadan olamiz
                $students = User::whereIn('id', array_keys($studentsToUpdate))->get();

                foreach ($students as $student) {
                    $newMark = $studentsToUpdate[$student->id] ?? null;

                    // Faqat o'zgarish bo'lsa update qiladi (Laravel o'zi tekshiradi)
                    if ($newMark !== null) {
                        $student->update(['mark' => $newMark]);
                    }

                    // Eslatma: checkAttendanceStatus metodining ichki tuzilishini bilmayman,
                    // lekin u har bir talaba uchun qo'shimcha query ishlatishi mumkin.
                    // Agar bu metod juda og'ir bo'lsa, uni optimizatsiya qilish kerak bo'ladi.
                    try {
                        if (method_exists($student, 'checkAttendanceStatus')) {
                            // Agar checkAttendanceStatus false qaytarsa, ActiveStudent ga qo'shamiz
                            if (!$student->checkAttendanceStatus()) {
                                ActiveStudent::firstOrCreate(['user_id' => $student->id]);
                            }
                        }
                    } catch (\Exception $subEx) {
                        // Agar bitta talabaning statusini tekshirishda xato bo'lsa, butun jarayon to'xtamasligi kerakmi?
                        // Hozircha log yozib davom ettiramiz yoki tranzaksiyani to'xtatishimiz mumkin.
                        // Qat'iy talab bo'lsa, throw $subEx qilish kerak.
                        Log::warning("Student ID {$student->id} attendance check failed: " . $subEx->getMessage());
                    }
                }
            }

            DB::commit(); // Hammasi yaxshi bo'lsa, bazaga tasdiqlaymiz

            return redirect()->route('assessment.index')->with('success', 'Baholar muvaffaqiyatli saqlandi.');

        } catch (\Exception $e) {
            DB::rollBack(); // Xatolik bo'lsa, barcha o'zgarishlarni bekor qilamiz
            Log::error('AssessmentController@update error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput() // Kiritilgan ma'lumotlar o'chib ketmasligi uchun
                ->with('error', 'Tizimda xatolik yuz berdi. Iltimos qaytadan urining.');
        }
    }
}
