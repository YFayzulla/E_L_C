<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Foydalanuvchi profilini tahrirlash formasini ko'rsatadi.
     */
    public function edit(Request $request): View
    {
        try {
            return view('profile.edit', [
                'user' => $request->user(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProfileController@edit error: ' . $e->getMessage());
            // Agar view ochishda xatolik bo'lsa, oddiy 500 sahifa o'rniga redirect qilish qiyin,
            // lekin try-catch qo'yish zararsiz.
            abort(500, 'Sahifani yuklashda xatolik yuz berdi.');
        }
    }

    /**
     * Foydalanuvchi profil ma'lumotlarini yangilaydi.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // Tranzaksiya boshlanishi
        DB::beginTransaction();

        try {
            $user = $request->user();
            $user->fill($request->validated());

            // Agar email o'zgargan bo'lsa, tasdiqlashni bekor qilish
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            DB::commit(); // Muvaffaqiyatli yakunlash

            return Redirect::route('profile.edit')->with('status', 'profile-updated');

        } catch (\Exception $e) {
            DB::rollBack(); // Xatolik bo'lsa, orqaga qaytarish
            Log::error('ProfileController@update error: ' . $e->getMessage());

            return Redirect::back()->with('error', 'Profilni yangilashda xatolik yuz berdi. Iltimos qaytadan urining.');
        }
    }

    /**
     * Foydalanuvchi hisobini o'chiradi.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        DB::beginTransaction();

        try {
            // Avval foydalanuvchini bazadan o'chiramiz
            // Agar User modelida "Cascading Delete" sozlanmagan bo'lsa,
            // unga bog'liq ma'lumotlarni shu yerda qo'lda o'chirish kerak bo'lishi mumkin.
            $user->delete();

            DB::commit();

            // Bazadan muvaffaqiyatli o'chirilgandan so'ng, sessiyani tozalaymiz.
            // Tartib muhim: agar DB da xato bo'lsa, foydalanuvchi log out bo'lmasligi kerak.
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ProfileController@destroy error: ' . $e->getMessage());

            return Redirect::route('profile.edit')->with('error', 'Hisobni o\'chirishda xatolik yuz berdi. Iltimos keyinroq urining.');
        }
    }
}