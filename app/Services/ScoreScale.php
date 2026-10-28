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
     * SQL ifoda — AVG() ichida ishlatish uchun. Ustunlar nomi chaqiruvchidan
     * keladi (foydalanuvchi kiritmaydi).
     */
    public static function sqlPercent(string $scoreColumn = 'score', string $maxColumn = 'max_score'): string
    {
        return "({$scoreColumn} * 100.0 / COALESCE(NULLIF({$maxColumn}, 0), " . self::DEFAULT_MAX . '))';
    }

    /** "50/50" ko'rinishi. */
    public static function fraction($score, $max): string
    {
        return $score === null ? '—' : ((int) $score) . '/' . ((int) $max > 0 ? (int) $max : self::DEFAULT_MAX);
    }
}
