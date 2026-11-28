<?php

namespace App\Services;

/**
 * Test foizidan CEFR darajasi (A1 … C2).
 *
 * Chegaralar markaz Sozlamalarida belgilanadi (`grading.cefr` — ro'yxat:
 * [['level' => 'A1', 'from' => 0], …]). Ro'yxat bo'sh bo'lsa daraja
 * ko'rsatilmaydi. Foiz — testning ball bo'yicha o'rtachasi
 * (ScoreScale::weighted).
 */
final class CefrScale
{
    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /** @return array<int, array{level: string, from: int}> pastdan yuqoriga */
    public static function bands(): array
    {
        return collect((array) config('grading.cefr', []))
            ->filter(fn ($band) => is_array($band)
                && in_array($band['level'] ?? null, self::LEVELS, true)
                && is_numeric($band['from'] ?? null))
            ->map(fn ($band) => ['level' => $band['level'], 'from' => max(0, min(100, (int) $band['from']))])
            ->sortBy('from')
            ->values()
            ->all();
    }

    public static function enabled(): bool
    {
        return self::bands() !== [];
    }

    /** Foizga mos daraja; eng past chegaradan ham past bo'lsa yoki foiz yo'q — null. */
    public static function levelFor(?int $percent): ?string
    {
        if ($percent === null) {
            return null;
        }

        $level = null;

        foreach (self::bands() as $band) {
            if ($percent >= $band['from']) {
                $level = $band['level'];
            }
        }

        return $level;
    }

    /** Daraja rangi (badge): A — sariq, B — ko'k, C — yashil. */
    public static function tone(?string $level): string
    {
        return match ($level[0] ?? null) {
            'A'     => 'warning',
            'B'     => 'info',
            'C'     => 'success',
            default => 'secondary',
        };
    }
}
