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
    use \App\Http\Controllers\Concerns\AuthorizesGroupAccess;

    /**
     * Baho qo'ya oladigan rollar.
     *
     * Marshrut guruhida `student` ham bor, chunki talaba O'Z natijalarini
     * shu kontrollerning index() amali orqali ko'radi. Lekin baholash
     * formasi unga tegishli emas.
     */
    private function assertGrader(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->hasRole('admin') || $user->hasRole('user') || $user->hasRole('support')),
            403,
            'Baho qo‘yish huquqi yo‘q.'
        );
    }

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
        // Bu sahifa BAHO QO'YISH formasi, ko'rish emas. Ilgari unda hech
        // qanday tekshiruv yo'q edi: istalgan o'qituvchi (va hatto talaba)
        // /assessment/{istalgan guruh} manzilini ochib, o'zga guruhga baho
        // qo'ya olardi. `role:` middleware faqat "u o'qituvchimi" degan
        // savolga javob beradi, "bu guruh uniki mi" degan savolga emas.
        $this->assertGrader();
        $this->assertTeachesGroup((int) $id);

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
        $this->assertGrader();
        $this->assertTeachesGroup((int) $id);

        $skills = (array) config('grading.skills');

        /*
         * The form is a grid — one row per student, one column per skill —
         * exactly like the Ko‘nikmalar page:
         *
         *     score[<student id>][<skill>] = 0..100
         *     comment[<student id>]        = free text
         *
         * One `assessments` row is written per filled cell, so the overall is
         * simply the mean of those rows. It is NOT stored: a sixth "overall"
         * row would make every average that reads this table count the same
         * marks twice, including the progress figure.
         */
        $request->validate([
            'student'      => 'required|array',
            'student.*'    => 'required|integer',
            'score'        => 'array',
            'score.*'      => 'array',
            'score.*.*'    => 'nullable|integer|min:0|max:100',
            'comment'      => 'array',
            'comment.*'    => 'nullable|string|max:250',
            'lesson'       => 'nullable|string|max:255',
        ], [
            'score.*.*.integer' => 'Ball butun son bo‘lishi kerak.',
            'score.*.*.max'     => 'Ball 0 dan 100 gacha bo‘lishi kerak.',
            'score.*.*.min'     => 'Ball 0 dan 100 gacha bo‘lishi kerak.',
        ]);

        $scores   = (array) $request->input('score', []);
        $comments = (array) $request->input('comment', []);
        $users    = (array) $request->input('student', []);

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

            // 3. Ma'lumotlarni tayyorlash — one row per filled cell
            $now = now();

            foreach ($users as $userId) {
                $userId = (int) $userId;

                if (! $userId) {
                    continue;
                }

                $given = [];

                foreach ($skills as $skill) {
                    $raw = $scores[$userId][$skill] ?? null;

                    // Blank means "not assessed": skipped, and kept out of the
                    // mean so it cannot drag the overall toward zero. A real 0
                    // IS a mark and is stored — the old `!= 0` check dropped it
                    // silently, so a student who scored nothing vanished from
                    // the record and from their progress figure.
                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    $mark    = (int) $raw;
                    $given[] = $mark;

                    $assessments[] = [
                        'get_mark'   => $mark,
                        'skill'      => $skill,
                        'user_id'    => $userId,
                        'for_what'   => $comments[$userId] ?? null,
                        'group'      => $group->name,
                        'history_id' => $history->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // users.mark is the "latest mark" on the student card, so it
                // gets the overall — the plain mean of whatever was scored.
                if ($given !== []) {
                    $studentsToUpdate[$userId] = (int) round(array_sum($given) / count($given));
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
