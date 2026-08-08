<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A reusable SMS wording.
 *
 * The body carries {placeholders}; render() swaps them for one student's real
 * values. Anything unrecognised is left alone rather than blanked, so a typo in
 * a placeholder is visible in the preview instead of silently producing
 * "Farzandingiz  darsga kelmadi".
 */
class SmsTemplate extends Model
{
    use \App\Models\Concerns\BelongsToCentre;

    use HasFactory;

    public const EVENTS = [
        'absence'  => 'Davomat — kelmadi',
        'late'     => 'Davomat — kechikdi',
        'grade'    => 'Baho / o‘zlashtirish',
        'homework' => 'Uy vazifasi',
        'payment'  => "To‘lov",
        'general'  => 'Umumiy',
    ];

    public const EVENT_ICONS = [
        'absence'  => 'bx-user-x',
        'late'     => 'bx-time-five',
        'grade'    => 'bx-medal',
        'homework' => 'bx-task',
        'payment'  => 'bx-wallet',
        'general'  => 'bx-message-dots',
    ];

    /**
     * Har bir yangi markaz shu sakkiztasi bilan ochiladi.
     *
     * Ilgari bu ro'yxat faqat migratsiya ichida INSERT qilingandi, ya'ni u
     * bir marta — birinchi markaz uchun — ishlagan va ikkinchi markaz
     * shablonsiz qolardi. Endi manba shu yerda; CentreProvisioner har bir
     * markazga o'z nusxasini yozadi.
     *
     * Oddiy qatorlar: markaz ularni erkin tahrirlaydi yoki o'chiradi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Darsga kelmadi',
                'slug' => 'absence',
                'event' => 'absence',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni {guruh} guruhidagi darsga kelmadi. {markaz}',
                'sort_order' => 10,
            ],
            [
                'name' => 'Darsga kechikdi',
                'slug' => 'late',
                'event' => 'late',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni {guruh} guruhidagi darsga kechikib keldi. {markaz}',
                'sort_order' => 20,
            ],
            [
                'name' => 'Bir necha darsni qoldirdi',
                'slug' => 'absence-repeated',
                'event' => 'absence',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} so‘nggi paytda {qoldirgan} ta darsni qoldirdi. Iltimos, biz bilan bog‘laning. {markaz}',
                'sort_order' => 30,
            ],
            [
                'name' => 'Test natijasi',
                'slug' => 'grade',
                'event' => 'grade',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} {sana} kuni bo‘lib o‘tgan testdan {baho} ball to‘pladi. {markaz}',
                'sort_order' => 40,
            ],
            [
                'name' => 'Oylik o‘zlashtirish',
                'slug' => 'progress',
                'event' => 'grade',
                'body' => 'Hurmatli ota-ona! {talaba}ning {guruh} guruhidagi o‘zlashtirishi: {reyting}%, davomati {davomat}%. {markaz}',
                'sort_order' => 50,
            ],
            [
                'name' => 'Uy vazifasini bajarmadi',
                'slug' => 'homework-missing',
                'event' => 'homework',
                'body' => 'Hurmatli ota-ona! Farzandingiz {talaba} uy vazifasini bajarmadi. Iltimos, e’tibor qarating. {markaz}',
                'sort_order' => 60,
            ],
            [
                'name' => 'To‘lov eslatmasi',
                'slug' => 'payment-reminder',
                'event' => 'payment',
                'body' => 'Hurmatli ota-ona! {talaba} uchun {oy} oyi to‘lovi kutilmoqda. Qarzdorlik: {qarz} so‘m. {markaz}',
                'sort_order' => 70,
            ],
            [
                'name' => 'Umumiy xabar',
                'slug' => 'general',
                'event' => 'general',
                'body' => 'Hurmatli ota-ona! ',
                'sort_order' => 90,
            ],
        ];
    }

    /** Placeholder => what it means, shown as help under the editor. */
    public const PLACEHOLDERS = [
        'talaba'    => 'Talabaning ismi',
        'guruh'     => 'Guruh nomi',
        'sana'      => 'Bugungi sana',
        'vaqt'      => 'Dars vaqti',
        'baho'      => 'Oxirgi test bahosi',
        'reyting'   => "O‘zlashtirish ko‘rsatkichi (%)",
        'davomat'   => 'Davomat foizi (%)',
        'qoldirgan' => 'Bu oyda qoldirgan darslari soni',
        'qarz'      => 'Qarzdorlik summasi',
        'oy'        => 'Joriy oy nomi',
        'ota_ona'   => 'Ota-onaning ismi',
        'oqituvchi' => "O‘qituvchining ismi",
        'markaz'    => "O‘quv markazi nomi",
    ];

    protected $fillable = ['name', 'slug', 'event', 'body', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event] ?? 'Umumiy';
    }

    public function eventIcon(): string
    {
        return self::EVENT_ICONS[$this->event] ?? 'bx-message-dots';
    }

    /**
     * Substitute {placeholders}. Unknown ones are left untouched on purpose —
     * see the class docblock.
     *
     * @param  array<string, string|int|null>  $context
     */
    public function render(array $context): string
    {
        $body = (string) $this->body;

        foreach ($context as $key => $value) {
            $body = str_replace('{' . $key . '}', (string) ($value ?? ''), $body);
        }

        return trim(preg_replace('/[ \t]+/', ' ', $body));
    }

    /**
     * Placeholder values for one student. Everything here is already loaded or a
     * single cheap lookup — this runs once per student, not per template.
     *
     * @return array<string, string|int>
     */
    public static function contextForStudent(User $student): array
    {
        $student->loadMissing(['groups', 'deptStudent']);

        $latestMark = Assessment::where('user_id', $student->id)
            ->orderByDesc('created_at')
            ->value('get_mark');

        $absencesThisMonth = Attendance::where('user_id', $student->id)
            ->whereIn('status', [0, 2])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $progress = null;

        try {
            $progress = app(\App\Services\ProgressService::class)->forStudent($student->id)['current'];
        } catch (\Throwable $e) {
            // A reporting failure must never stop someone sending an SMS.
        }

        $teacher = $student->groups
            ->flatMap(fn(Group $g) => $g->teachers)
            ->pluck('name')
            ->unique()
            ->implode(', ');

        return [
            'talaba'    => $student->name,
            'guruh'     => $student->groups->pluck('name')->implode(', ') ?: '—',
            'sana'      => now()->format('d.m.Y'),
            'vaqt'      => optional($student->groups->first())->start_time ?: '—',
            'baho'      => $latestMark !== null ? (string) $latestMark : '—',
            'reyting'   => $progress !== null ? (string) $progress : '—',
            'davomat'   => (string) $student->attendanceRate(),
            'qoldirgan' => (string) $absencesThisMonth,
            'qarz'      => number_format((int) optional($student->deptStudent)->dept, 0, '.', ' '),
            'oy'        => now()->translatedFormat('F'),
            'ota_ona'   => optional($student->guardians()->first())->name ?: 'ota-ona',
            'oqituvchi' => $teacher ?: '—',
            'markaz'    => config('app.name', 'ALPHA'),
        ];
    }

    /**
     * A slug that is unique, derived from the name.
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'shablon';
        $slug = $base;
        $i = 2;

        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
