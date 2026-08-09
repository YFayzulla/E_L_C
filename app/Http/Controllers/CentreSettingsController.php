<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Markaz admini o'zgartira oladigan sozlamalar.
 *
 * Super-admin panelidan farqi: u yerda platforma egasi markazning o'zini
 * boshqaradi (slug, holat, Eskiz hisobi). Bu yerda esa markazning O'Z
 * admini o'quv jarayoniga oid qarorlarni qabul qiladi.
 *
 * Qiymatlar `centres.settings` JSON ustuniga yoziladi va har bir so'rovda
 * ResolveCentre ularni `config('grading.*')` ustiga yozadi — ya'ni kod
 * bo'ylab tarqalgan o'nlab config o'qishlari o'zgarishsiz qoladi.
 */
class CentreSettingsController extends Controller
{
    /** Faqat shu kalitlar shu sahifadan boshqariladi. */
    private const EDITABLE = ['skill_mode'];

    public function edit()
    {
        $centre = Centre::current();

        abort_if($centre === null, 404, 'O‘quv markazi aniqlanmadi.');

        return view('admin.settings.edit', [
            'centre'    => $centre,
            'skillMode' => config('grading.skill_mode', 'skills'),
            // Namuna sifatida ko'rsatish uchun: standart ko'nikmalar ro'yxati
            // config'da qoladi, hatto 'single' usulida ham.
            'skillList' => (array) config('grading.skill_labels', []),
        ]);
    }

    public function update(Request $request)
    {
        $centre = Centre::current();

        abort_if($centre === null, 404, 'O‘quv markazi aniqlanmadi.');

        $data = $request->validate([
            'skill_mode' => ['required', Rule::in(['skills', 'single'])],
        ], [
            'skill_mode.in' => 'Baholash usuli noto‘g‘ri.',
        ]);

        // Faqat ruxsat etilgan kalitlar yoziladi va qolgan sozlamalar
        // saqlanadi — settings JSON boshqa narsalarni ham tutadi.
        $settings = (array) ($centre->settings ?? []);

        foreach (self::EDITABLE as $key) {
            if (array_key_exists($key, $data)) {
                $settings[$key] = $data[$key];
            }
        }

        $centre->update(['settings' => $settings]);

        $label = $data['skill_mode'] === 'single'
            ? 'bitta umumiy baho'
            : 'ko‘nikmalar bo‘yicha';

        return redirect()->route('settings.edit')->with(
            'success',
            "Baholash usuli saqlandi: {$label}. Eski baholar o‘z joyida qoldi."
        );
    }
}
