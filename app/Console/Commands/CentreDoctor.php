<?php

namespace App\Console\Commands;

use App\Models\Centre;
use App\Tenancy\TenantTables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenant holatini tekshiradi va nima buzilganini aytadi.
 *
 * Ko'chishdan keyin eng ko'p uchraydigan ikkita nosozlik jimgina o'tadi:
 *
 *   1. Foydalanuvchining `centre_user` qatori yo'q — u kira olmaydi
 *      ("Siz bu o'quv markaziga biriktirilmagansiz").
 *   2. `model_has_roles.centre_id` mavjud bo'lmagan yoki noto'g'ri markazga
 *      ishora qiladi — odam kiradi, lekin roli ko'rinmaydi va har bir
 *      `role:` bilan himoyalangan sahifa 403 qaytaradi.
 *
 * Ikkalasi ham xato bermaydi, log ham yozmaydi — shuning uchun ularni
 * qidirib topadigan alohida buyruq kerak.
 */
class CentreDoctor extends Command
{
    protected $signature = 'centre:doctor
                            {--fix : topilgan nosozliklarni tuzatish}
                            {--host= : shu host uchun markaz aniqlanishini sinash}';

    protected $description = 'Markazlar, a‘zoliklar va rol biriktirishlarni tekshiradi';

    private int $problems = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <options=bold>Multi-tenant tashxisi</>');

        $this->checkConfig();
        $centres = $this->checkCentres();

        if ($centres->isEmpty()) {
            return self::FAILURE;
        }

        $this->checkRoleDefinitions();
        $this->checkRoleAssignments($centres);
        $this->checkMemberships($centres);
        $this->checkOrphanRows($centres);
        $this->checkRuntime($centres);

        $this->newLine();

        if ($this->problems === 0) {
            $this->info('  Hammasi joyida — nosozlik topilmadi.');

            return self::SUCCESS;
        }

        $this->warn("  {$this->problems} ta nosozlik topildi.");

        if (! $this->option('fix')) {
            $this->line('  Tuzatish uchun: <comment>php artisan centre:doctor --fix</comment>');
        }

        $this->newLine();

        return self::FAILURE;
    }

    /* ================================================================ config */

    private function checkConfig(): void
    {
        $this->section('Sozlama');

        $rows = [
            ['permission.teams', config('permission.teams') ? '<info>true</info>' : '<error>false — MAJBURIY</error>'],
            ['team_foreign_key', (string) config('permission.column_names.team_foreign_key')],
            ['APP_DOMAIN', (string) config('app.domain')],
            ['APP_DEFAULT_CENTRE', config('app.default_centre') ?: '<comment>bo‘sh (apex = kirish sahifasi)</comment>'],
        ];

        $this->table(['', ''], $rows);

        if (! config('permission.teams')) {
            $this->fail('permission.teams o‘chirilgan — rollar markazga bog‘lanmaydi.');
        }
    }

    /* =============================================================== centres */

    private function checkCentres()
    {
        $this->section('Markazlar');

        $centres = Centre::withoutGlobalScopes()->orderBy('id')->get();

        if ($centres->isEmpty()) {
            $this->fail('Bironta ham markaz yo‘q. `php artisan centre:create` bilan oching.');

            return $centres;
        }

        $rows = [];

        foreach ($centres as $centre) {
            $waiting = $centre->waiting_room_group_id;
            $ok = $waiting && DB::table('groups')->where('id', $waiting)->exists();

            if (! $ok) {
                $this->problems++;
            }

            $rows[] = [
                $centre->id,
                $centre->slug,
                $centre->name,
                $centre->statusLabel(),
                $ok ? '#' . $waiting : '<error>YO\'Q</error>',
            ];
        }

        $this->table(['#', 'slug', 'nom', 'holat', 'kutish zali'], $rows);

        if ($this->option('fix')) {
            foreach ($centres as $centre) {
                if ($centre->waiting_room_group_id
                    && DB::table('groups')->where('id', $centre->waiting_room_group_id)->exists()) {
                    continue;
                }

                $group = Centre::for($centre, fn () => app(\App\Services\CentreProvisioner::class)
                    ->createWaitingRoom($centre));

                $this->info("  tuzatildi: «{$centre->slug}» uchun kutish zali yaratildi (guruh #{$group->id})");
            }
        }

        return $centres;
    }

    /* ====================================================== rol ta'riflari */

    private function checkRoleDefinitions(): void
    {
        $this->section('Rol ta’riflari');

        $table = config('permission.table_names.roles', 'roles');
        $roles = DB::table($table)->orderBy('name')->get();

        $expected = ['admin', 'user', 'student', 'parent'];
        $found = $roles->pluck('name')->unique()->all();

        foreach ($expected as $name) {
            if (! in_array($name, $found, true)) {
                $this->fail("«{$name}» roli umuman yo‘q — bu rol bilan hech kim kira olmaydi.");
            }
        }

        // Bir xil nomli bir nechta qator: Spatie qaysi birini olishi
        // markazga bog'liq bo'lib qoladi va biriktirish ko'rinmay qolishi mumkin.
        $dupes = $roles->groupBy('name')->filter(fn ($g) => $g->count() > 1);

        foreach ($dupes as $name => $group) {
            $ids = $group->pluck('id')->implode(', ');
            $centres = $group->pluck('centre_id')->map(fn ($c) => $c ?? 'NULL')->implode(', ');
            $this->fail("«{$name}» roli {$group->count()} marta bor (id: {$ids}; centre_id: {$centres}).");
        }

        if ($this->problems === 0) {
            $this->line('  <info>4 ta rol joyida, takror yo‘q.</info>');
        }
    }

    /* ================================================== rol biriktirishlari */

    private function checkRoleAssignments($centres): void
    {
        $this->section('Rol biriktirishlari');

        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roles = config('permission.table_names.roles', 'roles');
        $key = config('permission.column_names.team_foreign_key', 'centre_id');

        $valid = $centres->pluck('id')->all();

        $counts = DB::table($table . ' as mr')
            ->join($roles . ' as r', 'r.id', '=', 'mr.role_id')
            ->select('r.name', 'mr.' . $key . ' as centre', DB::raw('COUNT(*) as n'))
            ->groupBy('r.name', 'mr.' . $key)
            ->orderBy('mr.' . $key)
            ->orderBy('r.name')
            ->get();

        $rows = [];
        $orphans = 0;

        foreach ($counts as $row) {
            $known = in_array((int) $row->centre, $valid, true);

            if (! $known) {
                $orphans += (int) $row->n;
            }

            $rows[] = [
                $row->centre,
                $row->name,
                $row->n,
                $known ? '<info>ok</info>' : '<error>bunday markaz YO‘Q</error>',
            ];
        }

        $this->table(['markaz', 'rol', 'soni', ''], $rows);

        if ($orphans > 0) {
            $this->fail(
                "{$orphans} ta rol biriktirishi mavjud bo‘lmagan markazga ishora qilyapti. "
                . 'Bu odamlar kiradi, lekin ROLI KO‘RINMAYDI — har bir sahifa 403 qaytaradi.'
            );

            if ($this->option('fix')) {
                $target = $centres->first()->id;

                $fixed = DB::table($table)->whereNotIn($key, $valid)->update([$key => $target]);

                $this->info("  tuzatildi: {$fixed} ta biriktirish #{$target} markaziga o‘tkazildi.");
            }
        }
    }

    /* ============================================================ a'zoliklar */

    private function checkMemberships($centres): void
    {
        $this->section('A’zoliklar');

        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $key = config('permission.column_names.team_foreign_key', 'centre_id');

        // Markazda roli bor, lekin FAOL a'zoligi yo'q — kirishda to'xtatiladi.
        $missing = DB::table($table . ' as mr')
            ->join('users as u', 'u.id', '=', 'mr.model_id')
            ->leftJoin('centre_user as cu', function ($join) use ($key) {
                $join->on('cu.user_id', '=', 'mr.model_id')
                    ->on('cu.centre_id', '=', 'mr.' . $key);
            })
            ->whereNull('cu.user_id')
            ->select('mr.' . $key . ' as centre', 'u.id', 'u.name', 'u.phone')
            ->distinct()
            ->get();

        if ($missing->isEmpty()) {
            $this->line('  <info>Rolli foydalanuvchilarning hammasi a’zo.</info>');
        } else {
            $this->fail(
                $missing->count() . ' ta foydalanuvchining roli bor, lekin markazga A’ZO EMAS — '
                . 'ular kira olmaydi ("Siz bu o‘quv markaziga biriktirilmagansiz").'
            );

            $this->table(
                ['markaz', '#', 'ism', 'telefon'],
                $missing->take(20)->map(fn ($u) => [$u->centre, $u->id, $u->name, $u->phone])->all()
            );

            if ($missing->count() > 20) {
                $this->line('  … va yana ' . ($missing->count() - 20) . ' ta.');
            }

            if ($this->option('fix')) {
                $now = now();
                $rows = $missing->map(fn ($u) => [
                    'centre_id'  => $u->centre,
                    'user_id'    => $u->id,
                    'status'     => Centre::MEMBER_ACTIVE,
                    'is_default' => true,
                    'joined_at'  => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('centre_user')->insertOrIgnore($rows);
                $this->info('  tuzatildi: ' . count($rows) . ' ta a’zolik qo‘shildi.');
            }
        }

        // A'zoligi bor, lekin holati faol emas.
        $inactive = DB::table('centre_user')
            ->where('status', '!=', Centre::MEMBER_ACTIVE)
            ->count();

        if ($inactive > 0) {
            $this->warn("  {$inactive} ta a’zolik faol emas (taklif qilingan yoki to‘xtatilgan) — ular ham kira olmaydi.");
        }

        // Hech qaysi markazda roli yo'q foydalanuvchilar. Ular ham kira
        // olmaydi (`role:` middleware o'tkazmaydi), shuning uchun sonini
        // aytish yetarli emas — kimligini ko'rsatamiz.
        $roleless = DB::table('users as u')
            ->leftJoin($table . ' as mr', 'mr.model_id', '=', 'u.id')
            ->whereNull('mr.model_id')
            ->select('u.id', 'u.name', 'u.phone', 'u.email', 'u.created_at')
            ->orderBy('u.id')
            ->get();

        if ($roleless->isNotEmpty()) {
            $this->warn('  ' . $roleless->count()
                . ' ta foydalanuvchining hech qaysi markazda roli yo‘q — ular kira olmaydi:');

            $this->table(
                ['#', 'ism', 'telefon', 'pochta', 'yaratilgan'],
                $roleless->take(20)->map(fn ($u) => [
                    $u->id, $u->name, $u->phone, $u->email ?: '—', $u->created_at,
                ])->all()
            );

            $this->line('    Rol berish: markaz sahifasidan tahrirlang yoki '
                . 'kerak bo‘lmasa hisobni o‘chiring.');
        }
    }

    /* ====================================== markazi yo'q qatorlar (yetim) */

    private function checkOrphanRows($centres): void
    {
        $this->section('Ma’lumot qatorlari');

        $valid = $centres->pluck('id')->all();
        $bad = [];

        foreach (TenantTables::all() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'centre_id')) {
                continue;
            }

            $n = DB::table($table)->whereNotIn('centre_id', $valid)->count();

            if ($n > 0) {
                $bad[] = [$table, $n];
            }
        }

        if ($bad === []) {
            $this->line('  <info>Barcha qatorlar mavjud markazga tegishli.</info>');

            return;
        }

        $this->fail('Quyidagi jadvallarda mavjud bo‘lmagan markazga tegishli qatorlar bor:');
        $this->table(['jadval', 'qator'], $bad);

        if ($this->option('fix')) {
            $target = $centres->first()->id;

            foreach ($bad as [$table, $n]) {
                DB::table($table)->whereNotIn('centre_id', $valid)->update(['centre_id' => $target]);
                $this->info("  tuzatildi: {$table} — {$n} ta qator #{$target} markaziga o‘tkazildi.");
            }
        }
    }

    /* =============================================================== ish vaqti */

    /**
     * Eng jimgina nosozlik: baza to'g'ri, lekin so'rov paytida markaz
     * aniqlanmaydi.
     *
     * Markazsiz Spatie'ning team id si null bo'lib qoladi va `hasRole()`
     * HAMMA UCHUN false qaytaradi. Natijada `role:student` va `role:parent`
     * bilan himoyalangan sahifalar 403 beradi, adminning bosh sahifasi esa
     * (unda `role:` middleware yo'q) ochilaveradi — ya'ni "admin ishlaydi,
     * talaba 403" degan aynan o'sha manzara.
     */
    private function checkRuntime($centres): void
    {
        $this->section('Ish vaqti — markaz aniqlanishi');

        // Keshlangan config eng ko'p uchraydigan sabab: .env ga qator
        // qo'shilgan, lekin config:cache eskisini ushlab turibdi.
        if (file_exists($this->laravel->getCachedConfigPath())) {
            $this->fail(
                'Config KESHLANGAN (bootstrap/cache/config.php). .env dagi o‘zgarish '
                . 'kuchga kirmagan bo‘lishi mumkin — `php artisan config:clear` qiling.'
            );
        }

        $hosts = $this->option('host')
            ? [$this->option('host')]
            : array_values(array_unique(array_merge(
                [(string) config('app.domain')],
                $centres->map(fn ($c) => $c->slug . '.' . config('app.domain'))->all(),
                [parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost']
            )));

        $middleware = app(\App\Http\Middleware\ResolveCentre::class);
        $rows = [];

        foreach (array_filter($hosts) as $host) {
            $resolved = null;
            $note = '';

            try {
                $request = \Illuminate\Http\Request::create('http://' . $host . '/');
                $middleware->handle($request, function () { return new \Illuminate\Http\Response(); });
                $resolved = Centre::current();
            } catch (\Throwable $e) {
                $note = trim(class_basename($e) . ' ' . $e->getMessage());
            }

            $team = getPermissionsTeamId();

            // Apexda markaz bo'lmasligi — bu ATAYLAB shunday: u yerda kirish,
            // markaz tanlash va super-admin turadi. Shuning uchun bu holat
            // o'z-o'zidan nosozlik emas.
            $isApex = $host === (string) config('app.domain');

            $rows[] = [
                $host,
                $resolved ? '#' . $resolved->id . ' ' . $resolved->slug : ($isApex ? 'yo‘q' : '<error>YO‘Q</error>'),
                $team ?: ($isApex ? '—' : '<error>null</error>'),
                $resolved
                    ? '<info>ok</info>'
                    : ($isApex
                        ? '<comment>apex — kirish/tanlash sahifasi</comment>'
                        : ($note ?: '<error>role: 403 beradi</error>')),
            ];

            if (! $resolved && ! $isApex) {
                $this->problems++;
                $broken = true;
            }
        }

        $this->table(['host', 'markaz', 'team id', ''], $rows);

        // Aynan foydalanuvchi duch keladigan holat: bitta markaz bor, lekin
        // APP_DEFAULT_CENTRE bo'sh va odam apexdan kiryapti. Kirish o'tadi,
        // admin bosh sahifasi ham ochiladi (unda `role:` middleware yo'q),
        // lekin talaba va ota-ona sahifalari 403 qaytaradi.
        if ($centres->count() === 1 && blank(config('app.default_centre'))) {
            $this->fail(
                'Bitta markaz bor, lekin APP_DEFAULT_CENTRE bo‘sh. Agar apexdan '
                . '(subdomensiz) kirilsa markaz aniqlanmaydi va `hasRole()` HAMMA '
                . 'uchun false bo‘ladi: talaba va ota-ona sahifalari 403 beradi, '
                . 'adminning bosh sahifasi esa ochilaveradi.'
            );

            $this->line('    Yechim: .env ga <comment>APP_DEFAULT_CENTRE='
                . $centres->first()->slug . '</comment> qo‘shing va '
                . '<comment>php artisan config:clear</comment> qiling.');
        }

        if (! empty($broken)) {
            $this->line(
                '  <comment>Markaz aniqlanmagan subdomen bor — u yerda `hasRole()` '
                . 'false bo‘ladi va `role:` bilan himoyalangan sahifalar 403 qaytaradi.</comment>'
            );
        }

        // Haqiqiy foydalanuvchida tekshiramiz: rol ko'rinyaptimi.
        $first = $centres->first();

        Centre::for($first, function () use ($first) {
            foreach (['admin', 'user', 'student', 'parent'] as $role) {
                $n = \App\Models\User::role($role)->count();
                $total = DB::table(config('permission.table_names.model_has_roles', 'model_has_roles') . ' as mr')
                    ->join(config('permission.table_names.roles', 'roles') . ' as r', 'r.id', '=', 'mr.role_id')
                    ->where('r.name', $role)
                    ->where('mr.' . config('permission.column_names.team_foreign_key', 'centre_id'), $first->id)
                    ->count();

                $mark = $n === $total ? '<info>ok</info>' : '<error>MOS EMAS</error>';
                $this->line(sprintf('    %-8s ko‘rinadi: %-5d bazada: %-5d %s', $role, $n, $total, $mark));

                if ($n !== $total) {
                    $this->problems++;
                }
            }
        });
    }

    /* =============================================================== helpers */

    private function section(string $title): void
    {
        $this->newLine();
        $this->line("  <options=bold;fg=cyan>{$title}</>");
    }

    private function fail(string $message): void
    {
        $this->problems++;
        $this->line('  <error> ! </error> ' . $message);
    }
}
