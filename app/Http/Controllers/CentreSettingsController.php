<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use App\Services\CefrScale;
use App\Services\ScoreScale;
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
    private const EDITABLE = ['skill_mode', 'skill_max', 'cefr'];

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
            // Ko'nikma => maksimal ball (markazniki yoki standart 100).
            'skillMax'  => ScoreScale::maxes(array_keys((array) config('grading.skill_labels', []))),
            // Daraja => qaysi foizdan (bo'sh — ishlatilmaydi).
            'cefrFrom'  => collect(CefrScale::bands())->pluck('from', 'level')->all(),
        ]);
    }

    public function update(Request $request)
    {
        $centre = Centre::current();

        abort_if($centre === null, 404, 'O‘quv markazi aniqlanmadi.');

        $skillKeys = array_keys((array) config('grading.skill_labels', []));
        $limit = (int) config('grading.skill_max_limit', 1000);

        $data = $request->validate([
            'skill_mode'  => ['required', Rule::in(['skills', 'single'])],
            'skill_max'   => ['nullable', 'array'],
            'skill_max.*' => ['nullable', 'integer', 'min:1', "max:{$limit}"],
            'cefr_from'   => ['nullable', 'array'],
            'cefr_from.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ], [
            'cefr_from.*.integer' => 'CEFR chegarasi butun son (foiz) bo‘lishi kerak.',
            'cefr_from.*.max'     => 'CEFR chegarasi 100% dan oshmasligi kerak.',
            'skill_mode.in'       => 'Baholash usuli noto‘g‘ri.',
            'skill_max.*.integer' => 'Maksimal ball butun son bo‘lishi kerak.',
            'skill_max.*.min'     => 'Maksimal ball kamida 1 bo‘lishi kerak.',
            'skill_max.*.max'     => "Maksimal ball {$limit} dan oshmasligi kerak.",
        ]);

        // Faqat ma'lum ko'nikmalar; bo'sh qoldirilgani standart (100) ga qaytadi.
        $data['skill_max'] = collect((array) ($data['skill_max'] ?? []))
            ->only($skillKeys)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->all();

        // CEFR: to'ldirilgan darajalar, A1 → C2 tartibida chegarasi o'sib
        // borishi shart. Hammasi bo'sh — daraja ko'rsatilmaydi.
        $cefr = [];
        $previous = null;
        foreach (CefrScale::LEVELS as $level) {
            $from = $data['cefr_from'][$level] ?? null;

            if (! filled($from)) {
                continue;
            }

            if ($previous !== null && (int) $from <= $previous['from']) {
                return redirect()->back()->withInput()->withErrors([
                    "cefr_from.{$level}" => "{$level} chegarasi {$previous['level']} chegarasidan ({$previous['from']}%) katta bo‘lishi kerak.",
                ]);
            }

            $previous = ['level' => $level, 'from' => (int) $from];
            $cefr[] = $previous;
        }
        // Forma CEFR maydonlarisiz kelsa (eski forma, API) — sozlama tegilmaydi.
        if ($request->has('cefr_from')) {
            $data['cefr'] = $cefr;
        }
        unset($data['cefr_from']);

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
            "Sozlamalar saqlandi. Baholash usuli: {$label}. Eski baholar o‘z joyida qoldi."
        );
    }
}
