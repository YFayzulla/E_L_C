<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesGroupAccess;
use App\Models\GroupTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $target = User::role('user')->find($teacher);

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
}
