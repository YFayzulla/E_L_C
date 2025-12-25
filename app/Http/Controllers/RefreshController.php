<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RefreshController extends Controller
{
    /**
     * Foydalanuvchi statusini nollash (tiklash).
     *
     * @param int $id Foydalanuvchi IDsi
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(int $id)
    {
        DB::beginTransaction();

        try {
            // Userni qidirish
            $user = User::findOrFail($id);

            // Statusni yangilash
            // update() methodi bool qaytaradi, shuning uchun save() ishlatish shart emas
            $user->update(['status' => null]);

            DB::commit();

            return redirect()->back()->with('success', 'Foydalanuvchi statusi muvaffaqiyatli tiklandi.');

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Foydalanuvchi topilmadi.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RefreshController@update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Statusni yangilashda tizim xatoligi yuz berdi.');
        }
    }
}