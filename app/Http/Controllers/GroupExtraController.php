<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\StudentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GroupExtraController extends Controller
{
    use AuthorizesGroupAccess;

    public function __construct(protected AttendanceService $serviceAttendance)
    {
        // Service avtomatik inject qilinadi
    }

    /**
     * Ko'plab baholarni birdaniga o'chirish.
     */
    public function deleteMultiple(Request $request)
    {
        $request->validate(['selectedItems' => 'required|array']);

        try {
            // Test natijalari sahifasida bitta belgi — talabaning shu testdagi
            // barcha qatorlari ("12,13,14"), shuning uchun vergul bilan ajratiladi.
            $selectedItems = collect($request->input('selectedItems'))
                ->flatMap(fn ($item) => explode(',', (string) $item))
                ->map(fn ($item) => trim($item))
                ->all();

            // Xavfsizlik: Faqat raqamli ID larni ajratib olish
            $validatedItems = array_filter($selectedItems, 'ctype_digit');

            if (!empty($validatedItems)) {
                // Tranzaksiya shart emas (bitta query), lekin try-catch muhim
                Assessment::whereIn('id', $validatedItems)->delete();
            }

            return redirect()->back()->with('success', 'Tanlangan elementlar muvaffaqiyatli o\'chirildi.');

        } catch (\Exception $e) {
            Log::error('GroupExtraController@deleteMultiple error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'O\'chirish jarayonida xatolik yuz berdi.');
        }
    }

    /**
     * Belgilangan talabalarni boshqa guruhga ko'chirish.
     *
     * Har bir talaba `from_group_id` (va Kutish zali) dan chiqariladi va
     * `target_group_id` ga qo'shiladi; boshqa guruhlari saqlanadi. Yozuv
     * StudentGroupService orqali — oylik summa, qarzdorlik va guruh tarixi
     * birga yangilanadi, eski davomat o'z guruhida qoladi.
     */
    public function moveStudents(Request $request, StudentGroupService $membership)
    {
        $data = $request->validate([
            'student_ids'     => ['required', 'array', 'min:1'],
            'student_ids.*'   => ['integer'],
            'from_group_id'   => ['nullable', 'integer'],
            'target_group_id' => ['required', 'integer'],
        ], [
            'student_ids.required'     => 'Kamida bitta talabani belgilang.',
            'student_ids.min'          => 'Kamida bitta talabani belgilang.',
            'target_group_id.required' => 'Qaysi guruhga ko‘chirishni tanlang.',
        ]);

        $fromId = (int) ($data['from_group_id'] ?? 0);
        $waitingId = (int) Group::waitingRoomId();
        $toWaiting = $waitingId > 0 && (int) $data['target_group_id'] === $waitingId;

        $target = $toWaiting
            ? Group::query()->find($waitingId)
            : Group::query()->teaching()->find($data['target_group_id']);

        if (! $target) {
            return redirect()->back()->with('error', 'Tanlangan guruh topilmadi.');
        }

        $students = User::role('student')
            ->inCurrentCentre()
            ->whereIn('users.id', $data['student_ids'])
            ->get();

        $moved = 0;

        try {
            DB::transaction(function () use ($students, $membership, $fromId, $waitingId, $target, $toWaiting, &$moved) {
                foreach ($students as $student) {
                    $current = $student->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->all();

                    $targets = array_values(array_diff($current, array_filter([$fromId, $waitingId])));

                    // Kutish zaliga: faqat boshqa o'quv guruhi qolmagan talaba
                    // u yerga tushadi; boshqa guruhida o'qiyotgani shu guruhdan
                    // chiqariladi xolos.
                    if (! $toWaiting || $targets === []) {
                        $targets[] = (int) $target->id;
                    }

                    $targets = array_values(array_unique($targets));

                    sort($current);
                    $check = $targets;
                    sort($check);

                    if ($check === $current) {
                        continue; // allaqachon shu holatda
                    }

                    $membership->transfer($student, $targets, [], auth()->id());
                    $moved++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('GroupExtraController@moveStudents error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Talabalarni ko‘chirishda xatolik yuz berdi. O‘zgarishlar saqlanmadi.');
        }

        if ($moved === 0) {
            return redirect()->back()->with('warning', 'Belgilangan talabalar allaqachon shu guruhda.');
        }

        if ($toWaiting) {
            return redirect()->back()->with(
                'success',
                "{$moved} ta talaba guruhdan chiqarildi. Boshqa guruhi yo‘qlari Kutish zaliga o‘tkazildi."
            );
        }

        return redirect()->back()->with(
            'success',
            "{$moved} ta talaba «{$target->name}» guruhiga ko‘chirildi. Oylik to‘lov guruh narxi bo‘yicha qayta hisoblandi."
        );
    }

    /**
     * Talabaning guruhini o'zgartirish.
     */
    public function change_group(Request $request, $id)
    {
        $request->validate(['group_id' => 'required|array']);

        DB::beginTransaction();

        try {
            $user = User::inCurrentCentre()->findOrFail($id);
            $groupIds = $request->group_id;

            // 1. User guruhini yangilash (Eski guruhlardan chiqarib, yangisiga qo'shish)
            // Agar faqat qo'shish kerak bo'lsa attach() ishlatilardi, lekin "change" bo'lgani uchun sync()
            $user->groups()->sync($groupIds);

            // 2. Tarix (StudentInformation) yaratish
            $groups = Group::whereIn('id', $groupIds)->get();
            foreach($groups as $group){
                StudentInformation::create([
                    'user_id' => $user->id,
                    'group_id' => $group->id,
                    'group' => $group->name,
                    'actor_id' => auth()->id(),
                ]);
            }

            // 3. Eski davomatlarni yangi guruhga o'tkazish
            // (Mantiqan to'g'riligini loyiha talabidan kelib chiqib tekshiring.
            // Odatda eski davomat eski guruhda qolishi kerak, lekin sizning kodingizda o'zgartirilmoqda)
            // Agar ko'p guruh tanlansa, qaysi biriga o'tkazish noaniq bo'ladi.
            // Shuning uchun birinchi tanlangan guruhga o'tkazamiz yoki bu qismni o'chirib tashlaymiz.
            if (!empty($groupIds)) {
                Attendance::where('user_id', $user->id)->update(['group_id' => $groupIds[0]]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Guruh muvaffaqiyatli o\'zgartirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GroupExtraController@change_group error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhni o\'zgartirishda xatolik yuz berdi.');
        }
    }

    /**
     * Guruhdagi talabalar ro'yxatini ko'rsatish.
     */
    public function show($id)
    {
        $this->assertTeachesGroup((int) $id);

        try {
            // Guruhga tegishli talabalarni pivot jadval orqali olish
            $students = User::whereHas('groups', function ($query) use ($id) {
                    $query->where('groups.id', $id);
                })
                ->role('student')
                // Guruh nomini olish uchun relationshipni yuklaymiz
                ->with('groups:id,name')
                ->orderBy('name')
                ->orderBy('id')
                ->select('id', 'name', 'phone', 'status') // group_id olib tashlandi
                ->get();
                
            // "Belgilanganlarni ko'chirish" uchun: shu guruhdan boshqa o'quv
            // guruhlari va (bu guruhning o'zi bo'lmasa) Kutish zali.
            $group = Group::findOrFail($id);
            $groups = Group::query()->teaching()->active()
                ->where('id', '!=', $group->id)
                ->orderBy('name')->orderBy('id')
                ->get(['id', 'name']);
            $waitingRoom = $group->isWaitingRoom()
                ? null
                : Group::query()->find(Group::waitingRoomId(), ['id', 'name']);

            return view('admin.group.student', compact('students', 'group', 'groups', 'waitingRoom'));
        } catch (\Exception $e) {
            Log::error('GroupExtraController@show error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Talabalar ro\'yxatini yuklashda xatolik.');
        }
    }

    /**
     * Oylik davomat jadvalini shakllantirish (Matritsa).
     */
    public function attendance($id)
    {
        $this->assertTeachesGroup((int) $id);

        try {
            // Use the shared AttendanceService to get data
            $serviceData = $this->serviceAttendance->attendance($id);

            // Return the TEACHER view instead of the admin view
            return view('teacher.attendance.attendance', array_merge($serviceData, [
                'id' => $id,
            ]));

        } catch (\Exception $e) {
            Log::error('GroupExtraController@attendance error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Davomat jadvalini yuklashda xatolik.');
        }
    }

    /**
     * Excelga eksport qilish.
     */
    public function export($id)
    {
        $this->assertTeachesGroup((int) $id);

        try {
            $date = request('date');

            if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}$/', $date)) {
                $date = now()->format('Y-m');
            }

            [$year, $month] = array_map('intval', explode('-', $date));

            $group = Group::findOrFail($id);
            $fileName = 'davomat_' . $group->name . '_' . $year . '_' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.xlsx';

            return Excel::download(new AttendanceExport($id, $year, $month), $fileName);

        } catch (\Exception $e) {
            Log::error('GroupExtraController@export error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Excel faylini yuklashda xatolik yuz berdi.');
        }
    }
}
