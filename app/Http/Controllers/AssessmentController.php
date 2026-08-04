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
use Illuminate\Validation\Rule;

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
        $skills = (array) config('grading.skills');

        // 1. Validatsiya (Ma'lumotlar butunligini tekshirish)
        $request->validate([
            'student'   => 'required|array',
            'student.*' => 'required|integer',
            // Which skill each mark measured. Empty means "Umumiy" — a test
            // that is not about one skill — and is stored as NULL.
            'skill'     => 'array',
            'skill.*'   => ['nullable', Rule::in($skills)],
            'end_mark'  => 'array',
            'end_mark.*' => 'nullable|integer|min:0|max:100',
            'reason'    => 'array',
            'reason.*'  => 'nullable|string|max:255',
            'lesson'    => 'nullable|string|max:255',
        ], [
            'skill.*.in'       => 'Ko‘nikma noto‘g‘ri tanlandi.',
            'end_mark.*.max'   => 'Ball 0 dan 100 gacha bo‘lishi kerak.',
            'end_mark.*.min'   => 'Ball 0 dan 100 gacha bo‘lishi kerak.',
        ]);

        $end_marks = $request->input('end_mark', []);
        $reasons = $request->input('reason', []);
        $skillInput = $request->input('skill', []);
        $users = $request->input('student', []);

        if (empty($users)) {
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
            //
            // Iterate the students, not the reasons: the note is optional now
            // that the skill carries the meaning, and a blank note used to make
            // the whole row disappear.
            foreach ($users as $index => $userId) {
                if (! $userId) {
                    continue;
                }

                $raw  = $end_marks[$index] ?? null;
                $mark = ($raw === null || $raw === '') ? null : (int) $raw;

                // Blank means "not assessed" and is skipped. A real 0 is a
                // mark and IS stored — the old `$mark != 0` check dropped it
                // silently, so a student who scored nothing simply vanished
                // from the record and from their progress figure.
                if ($mark !== null) {
                    $skill = $skillInput[$index] ?? null;

                    $assessments[] = [
                        'get_mark'   => $mark,
                        // NULL = "Umumiy": a test that measures no one skill.
                        'skill'      => filled($skill) ? $skill : null,
                        'user_id'    => $userId,
                        'for_what'   => $reasons[$index] ?? null,
                        'group'      => $group->name,
                        'history_id' => $history->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // users.mark is the "latest mark" shown on the student
                    // card; only a real mark should move it.
                    $studentsToUpdate[$userId] = $mark;
                }
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
            
            // Xatolik haqida batafsil ma'lumotni logga yozish
            Log::error('AssessmentController@update error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'group_id' => $id,
                'input' => $request->all(),
                'exception' => $e
            ]);

            return redirect()->back()
                ->withInput() // Kiritilgan ma'lumotlar o'chib ketmasligi uchun
                ->with('error', 'Tizimda xatolik yuz berdi: ' . $e->getMessage());
        }
    }
}
