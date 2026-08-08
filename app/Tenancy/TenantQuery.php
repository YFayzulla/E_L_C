<?php

namespace App\Tenancy;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Xom so'rovlar uchun markaz filtri.
 *
 * `BelongsToCentre` global scope'i faqat Eloquent orqali ketgan so'rovlarga
 * qo'llanadi. `DB::table('group_teachers')` esa uni butunlay chetlab o'tadi —
 * bu shunchaki nazariy xavf emas: admin panelidagi o'qituvchilar jadvali
 * aynan shu sababdan boshqa markazning o'qituvchilarini ko'rsatardi.
 *
 * Har bir chaqiruv joyiga qo'lda `->where('centre_id', ...)` qo'shish yerga
 * mina ekish bilan barobar — bitta unutilgan joy jimgina oqadi. Shuning
 * uchun kirish nuqtasining o'zi almashtiriladi:
 *
 *     DB::table('group_teachers')      →  TenantQuery::table('group_teachers')
 *
 * Semantikasi CentreScope bilan bir xil: kontekst ataylab o'chirilgan
 * bo'lsa (konsol, super-admin) filtr qo'yilmaydi; aks holda markaz
 * majburiy va yo'qligi istisno tashlaydi, jimgina "hammasi" emas.
 */
class TenantQuery
{
    /**
     * Joriy markazga cheklangan xom so'rov quruvchisi.
     *
     * @param  string       $table  jadval nomi — TenantTables ro'yxatida bo'lishi shart
     * @param  string|null  $as     alias (join'larda kerak bo'ladi)
     */
    public static function table(string $table, ?string $as = null): Builder
    {
        self::assertScoped($table);

        $query = DB::table($as ? "{$table} as {$as}" : $table);

        $context = app(CentreContext::class);

        if ($context->isUnscoped()) {
            return $query;
        }

        return $query->where(($as ?: $table) . '.centre_id', $context->idOrFail());
    }

    /**
     * Join qilingan jadvalga ham markaz shartini qo'shadi.
     *
     * Join scope'dan tashqarida qoladi: `->join('group_user', ...)` qo'shilgan
     * jadvalning centre_id sini hech kim tekshirmaydi, ya'ni ikkita markazda
     * bir xil id lar uchrasa qatorlar aralashib ketadi.
     *
     *     ->leftJoin('group_user', function (JoinClause $join) {
     *          $join->on('group_user.group_id', '=', 'group_teachers.group_id');
     *          TenantQuery::constrain($join, 'group_user');
     *      })
     */
    public static function constrain(JoinClause $join, string $table, ?string $as = null): void
    {
        self::assertScoped($table);

        $context = app(CentreContext::class);

        if ($context->isUnscoped()) {
            return;
        }

        $join->where(($as ?: $table) . '.centre_id', '=', $context->idOrFail());
    }

    /** Joriy markaz id si — xom `whereRaw` va shu kabilar uchun. */
    public static function centreId(): ?int
    {
        $context = app(CentreContext::class);

        return $context->isUnscoped() ? null : $context->idOrFail();
    }

    /**
     * Jadval haqiqatan markazga tegishlimi.
     *
     * Xato yozilgan nom yoki platforma jadvali (`users`, `centres`) berilsa,
     * `where centre_id` mavjud bo'lmagan ustunga tushib SQL xatosi bo'lardi —
     * yoki, yomoni, kelajakda kimdir shu yerga platforma jadvalini qo'shib
     * qo'ysa, filtr jimgina hech nima qilmasdi.
     */
    private static function assertScoped(string $table): void
    {
        if (! in_array($table, TenantTables::all(), true)) {
            throw new RuntimeException(
                "«{$table}» markazga tegishli jadval emas. TenantQuery faqat "
                . 'centre_id ustuni bor jadvallar uchun; platforma jadvallariga '
                . '(users, centres, centre_user, parent_student) oddiy DB::table() ishlating.'
            );
        }
    }
}
