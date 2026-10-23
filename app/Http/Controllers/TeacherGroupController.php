<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\Group;
use App\Models\GroupTeacher;
use App\Models\User;
use App\Services\StudentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * O'qituvchining guruhlarini biriktirish va uzish (group_teachers pivot jadvali).
 *
 * ExtraTeacherController::add_group o'rniga - `teacher_group.store` marshruti
 * hech qachon mavjud bo'lmagan o'sha metodga ishora qilib turgan edi.
 */
class TeacherGroupController extends Controller
{
    use AuthorizesGroupAccess;

    /**
     * O'qituvchiga bir yoki bir nechta guruhni biriktirish.
     *
     * group_teachers da unique indeks yo'q, shuning uchun har bir guruh
     * firstOrCreate orqali qo'shiladi - takror qator paydo bo'lmaydi.
     */
    public function attach(Request $request, int $teacher)
    {
        $validated = $request->validate([
            'group_id' => ['required', 'array'],
            'group_id.*' => ['integer', 'exists:groups,id'],
        ], [
            'group_id.required' => 'Kamida bitta guruh tanlang.',
            'group_id.array' => 'Guruhlar ro\'yxati noto\'g\'ri yuborildi.',
            'group_id.*.integer' => 'Guruh tanlovi noto\'g\'ri.',
            'group_id.*.exists' => 'Tanlangan guruhlardan biri topilmadi.',
        ]);

        $groupIds = array_values(array_unique(array_map('intval', $validated['group_id'])));

        // Tranzaksiyadan TASHQARIDA: abort() ham \Exception, catch uni yutib yuboradi.
        $target = User::role(TeacherController::STAFF_ROLES)->find($teacher);

        if (! $target) {
            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');
        }

        $this->assertTeachesGroups($groupIds);

        DB::beginTransaction();

        try {
            $added = 0;

            foreach ($groupIds as $groupId) {
                $row = GroupTeacher::firstOrCreate([
                    'teacher_id' => $target->id,
                    'group_id' => $groupId,
                ]);

                if ($row->wasRecentlyCreated) {
                    $added++;
                }
            }

            DB::commit();

            if ($added === 0) {
                return redirect()->back()->with('warning', 'Tanlangan guruhlar allaqachon biriktirilgan.');
            }

            return redirect()->back()->with('success', $added . ' ta guruh o\'qituvchiga biriktirildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherGroupController@attach error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Guruhni biriktirishda xatolik yuz berdi.');
        }
    }

    /**
     * Biriktirishni uzish. {id} - group_teachers jadvalidagi QATOR id si.
     */
    public function detach(int $id)
    {
        $row = GroupTeacher::find($id);

        if (! $row) {
            return redirect()->back()->with('error', 'Bunday biriktirish topilmadi.');
        }

        // Tranzaksiyadan tashqarida - abort() ni catch yutib yubormasligi uchun.
        $this->assertTeachesGroup((int) $row->group_id);

        DB::beginTransaction();

        try {
            $row->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Guruh o\'qituvchidan ajratildi.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherGroupController@detach error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Guruhni ajratishda xatolik yuz berdi.');
        }
    }

    /**
     * Mavjud talabani shu o'qituvchining guruhiga qo'shish (o'qituvchi
     * sahifasidan, admin).
     *
     * Talabaning boshqa guruhlari saqlanadi, faqat Kutish zalidan chiqariladi —
     * haqiqiy guruhga tushgan talaba endi kutmayapti. Yozuv
     * StudentGroupService orqali: summa, qarzdorlik va tarix birga yangilanadi.
     */
    public function addStudent(Request $request, int $teacher, StudentGroupService $membership)
    {
        $target = User::role(TeacherController::STAFF_ROLES)->find($teacher);

        if (! $target) {
            return redirect()->route('teacher.index')->with('error', 'O\'qituvchi topilmadi.');
        }

        $teacherGroupIds = GroupTeacher::where('teacher_id', $target->id)
            ->pluck('group_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $request->merge([
            'payment' => str_replace([' ', ',', "\u{00A0}"], '', (string) $request->input('payment')) ?: null,
        ]);

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'group_id' => ['required', 'integer', Rule::in($teacherGroupIds)],
            'payment' => ['nullable', 'numeric', 'min:0'],
        ], [
            'student_id.required' => 'Talabani tanlang.',
            'group_id.required' => 'Guruhni tanlang.',
            'group_id.in' => 'Bu guruh shu o\'qituvchiga biriktirilmagan.',
            'payment.numeric' => 'Oylik to\'lov raqam bo\'lishi kerak.',
        ]);

        $student = User::role('student')->inCurrentCentre()->find($validated['student_id']);

        if (! $student) {
            return redirect()->back()->withInput()->withErrors(['student_id' => 'Talaba topilmadi.']);
        }

        $groupId = (int) $validated['group_id'];
        $current = $student->groups()->pluck('groups.id')->map(fn($id) => (int) $id)->all();

        if (in_array($groupId, $current, true)) {
            return redirect()->back()->with('warning', $student->name . ' allaqachon shu guruhda.');
        }

        $targets = array_values(array_diff($current, [(int) Group::waitingRoomId()]));
        $targets[] = $groupId;

        try {
            $membership->transfer(
                $student,
                $targets,
                filled($validated['payment'] ?? null) ? [$groupId => $validated['payment']] : [],
                auth()->id()
            );
        } catch (\Throwable $e) {
            Log::error('TeacherGroupController@addStudent error: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Talabani guruhga qo\'shishda xatolik yuz berdi.');
        }

        $groupName = Group::whereKey($groupId)->value('name');

        return redirect()->back()->with('success', $student->name . ' «' . $groupName . '» guruhiga qo\'shildi.');
    }
}
