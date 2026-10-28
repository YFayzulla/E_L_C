<?php

namespace App\Http\Controllers;

use App\Models\ActiveStudent;
use App\Models\Assessment;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\LessonAndHistory;
use App\Models\User;
use App\Services\ScoreScale;
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
                $groups = Group::orderBy('name')->orderBy('id')->get();
                return view('admin.assessment.index', compact('groups'));
            }

            // Talaba — o'z natijalari. Ilgari u quyidagi o'qituvchi
            // tarmog'iga tushib, "Guruhlarni yuklashda xatolik" olardi.
            if ($user->hasRole('student')) {
                // Bitta imtihon — bitta qator (ko'nikmalari va o'rtachasi bilan).
                $tests = Assessment::testsFor($user->id);

                return view('student.results', compact('tests'));
            }

            // Teacher sees only their groups. Saralash guruh nomi bo'yicha
            // PHP'da: `group_teachers` da `name` ustuni yo'q, SQL'dagi
            // orderBy('name') aynan shu sahifani yiqitardi.
            $groups = GroupTeacher::where('teacher_id', $user->id)
                ->orderBy('id')
                ->with('group')
                ->get()
                ->filter(fn ($link) => $link->group !== null)
                ->sortBy(fn ($link) => mb_strtolower($link->group->name), SORT_NATURAL)
                ->values();

            return view('teacher.assessment.index', compact('groups'));
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
                ->orderBy('id')
                ->get();

            // Sahifa tepasidagi guruh tanlash: o'qituvchi faqat o'z guruhlarini,
            // admin hammasini ko'radi. Kutish zali — o'quv guruhi emas.
            $groupOptions = Group::whereIn('id', $this->accessibleGroupIds())
                ->teaching()
                ->orderBy('id')
                ->get(['id', 'name'])
                ->sortBy(fn ($option) => mb_strtolower($option->name), SORT_NATURAL)
                ->values();

            return view('teacher.assessment.make_markes', [
                'students' => $students,
                'id' => $id,
                'groups' => $groupOptions,
                'groupName' => $group->name,
                // Ustun sarlavhasidagi maksimum — markaz sozlamasidan.
                'skillMaxes' => ScoreScale::maxes((array) config('grading.skills', [])),
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

        $allSkills = (array) config('grading.skills');

        /*
         * The form is a grid — one row per student, one column per skill —
         * exactly like the Ko‘nikmalar page:
         *
         *     skills[]                     = ustun tepasida belgilangan ko'nikmalar
         *     max[<skill>]                 = shu testdagi maksimal ball (standart — Sozlamalar)
         *     score[<student id>][<skill>] = 0..max (xom ball)
         *     comment[<student id>]        = free text
         *
         * Test har doim ham to'rt ko'nikmani qamramaydi: guruh faqat Writing
         * topshirgan bo'lsa, o'qituvchi faqat Writing ustunini belgilaydi.
         * Shuning uchun faqat belgilangan ustunlar saqlanadi va o'rtacha ham
         * faqat ular bo'yicha hisoblanadi (Grammatika belgilangan bo'lsa u ham
         * kiradi). One `assessments` row is written per filled cell.
         */
        // Har bir ko'nikmaning shu testdagi maksimumi: formadan, bo'lmasa
        // markaz sozlamasidan. Ball shu maksimumdan oshmasligi kerak.
        $maxes = [];
        foreach ($allSkills as $skill) {
            $maxes[$skill] = ScoreScale::clampMax($request->input("max.{$skill}") ?: ScoreScale::max($skill));
        }

        $limit = (int) config('grading.skill_max_limit', 1000);
        $rules = [
            'student'      => 'required|array',
            'student.*'    => 'required|integer',
            'skills'       => 'required|array|min:1',
            'skills.*'     => ['string', Rule::in($allSkills)],
            'max'          => 'nullable|array',
            'max.*'        => "nullable|integer|min:1|max:{$limit}",
            'score'        => 'array',
            'score.*'      => 'array',
            'score.*.*'    => 'nullable|integer|min:0',
            'comment'      => 'array',
            'comment.*'    => 'nullable|string|max:250',
            'lesson'       => 'nullable|string|max:255',
        ];
        $messages = [
            'skills.required'   => 'Kamida bitta ko‘nikma ustunini belgilang.',
            'skills.min'        => 'Kamida bitta ko‘nikma ustunini belgilang.',
            'skills.*.in'       => 'Noma’lum ko‘nikma tanlandi.',
            'max.*.min'         => 'Maksimal ball kamida 1 bo‘lishi kerak.',
            'max.*.max'         => "Maksimal ball {$limit} dan oshmasligi kerak.",
            'score.*.*.integer' => 'Ball butun son bo‘lishi kerak.',
            'score.*.*.min'     => 'Ball manfiy bo‘lishi mumkin emas.',
        ];
        foreach ($maxes as $skill => $max) {
            $rules["score.*.{$skill}"] = "nullable|integer|min:0|max:{$max}";
            $messages["score.*.{$skill}.max"] = "Ball {$max} dan oshmasligi kerak.";
        }

        $request->validate($rules, $messages);

        // Config tartibida — belgilanmagan ustunning balli (qo'lda yuborilgan
        // bo'lsa ham) saqlanmaydi.
        $skills = array_values(array_intersect($allSkills, (array) $request->input('skills', [])));

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

                $givenOverall = [];

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

                    $mark = (int) $raw;
                    $givenOverall[] = ScoreScale::percent($mark, $maxes[$skill]);

                    $assessments[] = [
                        'get_mark'   => $mark,
                        'max_score'  => $maxes[$skill],
                        'skill'      => $skill,
                        'user_id'    => $userId,
                        'for_what'   => $comments[$userId] ?? null,
                        'group'      => $group->name,
                        'history_id' => $history->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // users.mark ("Oxirgi ball") — belgilangan ustunlar foizlari
                // o'rtachasi (50/50 — 100). Yig'indi faqat jadvalda ko'rinadi.
                if ($givenOverall !== []) {
                    $studentsToUpdate[$userId] = (int) round(array_sum($givenOverall) / count($givenOverall));
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
