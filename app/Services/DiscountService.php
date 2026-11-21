<?php

namespace App\Services;

use App\Models\Group;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Tenancy\TenantQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Talaba chegirmalari va ular bilan oylik summani hisoblash.
 *
 * group_user.base_payment — asl (kelishilgan) narx, admin kiritadi;
 * group_user.payment     — chegirmadan keyin to'lanadigan summa.
 *
 * Qarzdorlik (should_pay, dept_students.dept), to'lov qabul qilish va
 * o'qituvchi oyligi faqat `payment` ni o'qiydi, shuning uchun ular
 * chegirmani o'zgarishsiz hisobga oladi. Bir vaqtda bir nechta chegirma
 * amalda bo'lsa — eng kattasi (qo'shilmaydi).
 */
class DiscountService
{
    /**
     * Talabaga chegirma berish.
     */
    public function give(
        User $student,
        int $percent,
        ?int $groupId,
        $startsOn,
        $endsOn = null,
        ?string $reason = null,
        ?string $batch = null
    ): StudentDiscount {
        return DB::transaction(function () use ($student, $percent, $groupId, $startsOn, $endsOn, $reason, $batch) {
            $discount = StudentDiscount::create([
                'user_id'    => $student->id,
                'group_id'   => $groupId,
                'percent'    => max(1, min(100, $percent)),
                'starts_on'  => Carbon::parse($startsOn)->toDateString(),
                'ends_on'    => $endsOn ? Carbon::parse($endsOn)->toDateString() : null,
                'reason'     => filled($reason) ? trim($reason) : null,
                'batch'      => $batch,
                'created_by' => auth()->id(),
            ]);

            $this->recalculate($student);

            return $discount;
        });
    }

    /**
     * Guruhdagi barcha (hozirgi) talabaga bir xil chegirma.
     *
     * @return int nechta talabaga berildi
     */
    public function giveToGroup(Group $group, int $percent, $startsOn, $endsOn = null, ?string $reason = null): int
    {
        $students = $group->students()->get();
        $batch = (string) Str::uuid();

        DB::transaction(function () use ($students, $group, $percent, $startsOn, $endsOn, $reason, $batch) {
            foreach ($students as $student) {
                $this->give($student, $percent, (int) $group->id, $startsOn, $endsOn, $reason, $batch);
            }
        });

        return $students->count();
    }

    public function cancel(StudentDiscount $discount): void
    {
        DB::transaction(function () use ($discount) {
            $discount->update(['cancelled_at' => now(), 'cancelled_by' => auth()->id()]);

            if ($discount->student) {
                $this->recalculate($discount->student);
            }
        });
    }

    /** Shu guruh uchun bugun amaldagi eng katta chegirma foizi. */
    public function percentFor(User $student, int $groupId, $date = null): int
    {
        return (int) StudentDiscount::where('user_id', $student->id)
            ->activeOn($date)
            ->where(fn ($q) => $q->whereNull('group_id')->orWhere('group_id', $groupId))
            ->max('percent');
    }

    /**
     * Talabaning har bir guruhida to'lanadigan summani asl narx va amaldagi
     * chegirmadan qayta hisoblaydi; should_pay va dept ni yig'indiga keltiradi.
     * Faqat o'zgargan qiymatlar yoziladi — istalgancha chaqirish xavfsiz.
     */
    public function recalculate(User $student, $date = null): void
    {
        $rows = TenantQuery::table('group_user')
            ->where('user_id', $student->id)
            ->get(['group_id', 'payment', 'base_payment']);

        $discounts = StudentDiscount::where('user_id', $student->id)->activeOn($date)->get();

        $sum = 0;

        foreach ($rows as $row) {
            $base = $row->base_payment ?? $row->payment;
            $base = (int) ($base ?? 0);

            $percent = (int) $discounts
                ->filter(fn (StudentDiscount $d) => $d->appliesToGroup((int) $row->group_id))
                ->max('percent');

            $effective = self::apply($base, $percent);
            $sum += $effective;

            if ((int) $row->payment !== $effective || $row->base_payment === null) {
                TenantQuery::table('group_user')
                    ->where('user_id', $student->id)
                    ->where('group_id', $row->group_id)
                    ->update(['payment' => $effective, 'base_payment' => $base]);
            }
        }

        if ((int) $student->should_pay !== $sum) {
            $student->forceFill(['should_pay' => $sum])->save();
        }

        $dept = $student->deptStudent()->first();

        if (! $dept) {
            $student->deptStudent()->create(['dept' => $sum, 'payed' => 0, 'status_month' => 0]);
        } elseif ((int) $dept->dept !== $sum) {
            $dept->update(['dept' => $sum]);
        }
    }

    /** Muddati boshlangan yoki tugagan chegirmalar uchun (har kuni). */
    public function refreshDue($date = null): int
    {
        $day = Carbon::parse($date ?? today());

        // Bekor qilinmagan va yaqinda tugamagan chegirmasi bor talabalar —
        // ikki kunlik zaxira, tunda ishga tushmay qolgan kun uchun.
        $userIds = StudentDiscount::whereNull('cancelled_at')
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $day->copy()->subDays(2)->toDateString()))
            ->distinct()
            ->pluck('user_id');

        foreach (User::whereIn('id', $userIds)->get() as $student) {
            $this->recalculate($student, $day);
        }

        return $userIds->count();
    }

    /** 400 000 dan 20% — 320 000. */
    public static function apply(int $base, int $percent): int
    {
        if ($percent <= 0) {
            return $base;
        }

        return (int) round($base * (100 - min(100, $percent)) / 100);
    }
}
