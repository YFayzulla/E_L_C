<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\GroupTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExtraTeacherController extends Controller
{
    /**
     * O'qituvchi va guruh bog'lanishini o'chirish.
     *
     * @param int $id GroupTeacher ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function group_delete(int $id)
    {
        DB::beginTransaction();

        try {
            $groupTeacher = GroupTeacher::findOrFail($id);
            $groupTeacher->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Guruh birikmasi muvaffaqiyatli o\'chirildi.');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'O\'chirilishi kerak bo\'lgan ma\'lumot topilmadi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExtraTeacherController@group_delete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Guruhni o\'chirishda xatolik yuz berdi.');
        }
    }

    /**
     * Davomat yozuvini o'chirish.
     *
     * @param int $id Attendance ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attendanceDelete(int $id)
    {
        DB::beginTransaction();

        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Davomat yozuvi muvaffaqiyatli o\'chirildi.');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Davomat ma\'lumoti topilmadi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExtraTeacherController@attendanceDelete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Davomatni o\'chirishda xatolik yuz berdi.');
        }
    }
}