<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Platforma egasini belgilash.
 *
 * Super-admin — Spatie roli EMAS va bo'la olmaydi: `model_has_roles.centre_id`
 * NOT NULL va birlamchi kalit tarkibida, ya'ni markazsiz rol biriktirish
 * imkonsiz. Super-admin esa aynan markazlardan yuqorida turadi —
 * `users.is_super_admin` bayrog'i + Gate::before.
 *
 * Shu paytgacha bu bayroqni yoqishning yagona yo'li bazaga qo'lda UPDATE
 * yozish edi.
 */
class UserSuperAdmin extends Command
{
    protected $signature = 'user:super-admin
                            {phone : telefon raqami}
                            {--revoke : bermaslik, aksincha olib tashlash}
                            {--list : hozirgi super-adminlar ro‘yxati}';

    protected $description = 'Foydalanuvchiga super-admin huquqini beradi yoki olib tashlaydi';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listOwners();
        }

        $phone = User::normalizePhone((string) $this->argument('phone'));

        $user = $phone ? User::where('phone', $phone)->first() : null;

        if ($user === null) {
            $this->error("«{$this->argument('phone')}» raqamli foydalanuvchi topilmadi.");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');

        if ((bool) $user->is_super_admin === $grant) {
            $this->line($user->name . ' allaqachon ' . ($grant ? 'super-admin.' : 'super-admin emas.'));

            return self::SUCCESS;
        }

        if (! $grant) {
            $others = User::where('is_super_admin', true)->where('id', '!=', $user->id)->count();

            // Oxirgi super-adminni olib tashlash — o'zini o'zi qulflab
            // qo'yish: markaz ocha oladigan hech kim qolmaydi va uni
            // qaytarishning yagona yo'li yana bazaga qo'lda kirish bo'ladi.
            if ($others === 0 && ! $this->confirm(
                'Bu — oxirgi super-admin. Olib tashlansa, markaz ochadigan '
                . 'hech kim qolmaydi. Davom etilsinmi?',
                false
            )) {
                $this->line('Bekor qilindi.');

                return self::SUCCESS;
            }
        }

        $user->forceFill(['is_super_admin' => $grant])->save();

        $this->info($user->name . ' (' . $user->phone . ') — '
            . ($grant ? 'super-admin qilindi.' : 'super-adminlikdan olindi.'));

        return self::SUCCESS;
    }

    private function listOwners(): int
    {
        $owners = User::where('is_super_admin', true)->orderBy('id')->get(['id', 'name', 'phone', 'email']);

        if ($owners->isEmpty()) {
            $this->warn('Bironta ham super-admin yo‘q — markaz ocha oladigan odam yo‘q.');

            return self::SUCCESS;
        }

        $this->table(
            ['#', 'ism', 'telefon', 'pochta'],
            $owners->map(fn ($u) => [$u->id, $u->name, $u->phone, $u->email ?: '—'])->all()
        );

        return self::SUCCESS;
    }
}
