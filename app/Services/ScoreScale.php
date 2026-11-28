<?php

namespace App\Services;

/**
 * Ko'nikma va oylik test ballari: xom ball + o'z maksimumi.
 *
 * Ball xom holda saqlanadi ("50"), yonida qaysi maksimumdan qo'yilgani
 * (`max_score`, masalan 50). Taqqoslash, o'rtacha, rang va o'zlashtirish
 * har doim foizda: 50/50 — 100%, 25/50 — 50%. Uy vazifalari ham xuddi
 * shunday ishlaydi (homeworks.max_score).
 *
 * Maksimumi yo'q eski qatorlar uchun 100 — ya'ni ular avvalgidek o'qiladi.
 */
final class ScoreScale
{
    public const DEFAULT_MAX = 100;

    /** Markaz sozlamasidagi maksimum (config grading.skill_max). */
    public static function max(?string $skill): int
    {
        $value = $skill === null ? null : config("grading.skill_max.{$skill}");

        return self::clampMax($value);
    }

    /**
     * Ko'nikma => maksimum, berilgan tartibda.
     *
     * @param  array<int, string>  $skills
     * @return array<string, int>
     */
    public static function maxes(array $skills): array
    {
        $out = [];

        foreach ($skills as $skill) {
            $out[$skill] = self::max($skill);
        }

        return $out;
    }

    public static function clampMax($value): int
    {
        $value = (int) $value;

        if ($value < 1) {
            return self::DEFAULT_MAX;
        }

        return min($value, (int) config('grading.skill_max_limit', 1000));
    }

    /** Foiz, butun songa yaxlitlangan; ball yo'q bo'lsa null. */
    public static function percent($score, $max): ?int
    {
        if ($score === null || $score === '') {
            return null;
        }

        $max = (int) $max > 0 ? (int) $max : self::DEFAULT_MAX;

        return (int) round(((float) $score) * 100 / $max);
    }

    /**
     * Testning o'rtachasi — ball bo'yicha: olingan ballar yig'indisi /
     * maksimumlar yig'indisi. Grammatika 47/50, O'qish 18/25, Tinglash 21/25
     * → 86/100 = 86%. Har bir ko'nikma o'z bali og'irligida kiradi (foizlarning
     * oddiy o'rtachasi 83% chiqarib, 50 ballik bo'limni kam hisoblardi).
     *
     * @param  iterable<array{mark: int|float|null, max: int|null}>  $marks
     */
    public static function weighted(iterable $marks): ?int
    {
        $sum = 0.0;
        $max = 0;

        foreach ($marks as $item) {
            if (($item['mark'] ?? null) === null || $item['mark'] === '') {
                continue;
            }

            $sum += (float) $item['mark'];
            $max += self::clampMax($item['max'] ?? null);
        }

        return $max > 0 ? (int) round($sum * 100 / $max) : null;
    }

    /**
     * SQL ifoda — AVG() ichida ishlatish uchun. Ustunlar nomi chaqiruvchidan
     * keladi (foydalanuvchi kiritmaydi).
     */
    public static function sqlPercent(string $scoreColumn = 'score', string $maxColumn = 'max_score'): string
    {
        return "({$scoreColumn} * 100.0 / COALESCE(NULLIF({$maxColumn}, 0), " . self::DEFAULT_MAX . '))';
    }

    /**
     * Ball ko'rsatish va hisob uchun: butun bo'lsa int (47), kasr bo'lsa
     * float (6.5). Bazadagi decimal "47.00" ni "47" qilib beradi.
     */
    public static function mark($score): int|float|null
    {
        if ($score === null || $score === '') {
            return null;
        }

        $value = round((float) $score, 2);

        return floor($value) === $value ? (int) $value : $value;
    }

    /** "50/50", "6.5/9" ko'rinishi. */
    public static function fraction($score, $max): string
    {
        return $score === null ? '—' : self::mark($score) . '/' . ((int) $max > 0 ? (int) $max : self::DEFAULT_MAX);
    }
}
