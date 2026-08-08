<?php

namespace App\Console\Commands;

use App\Models\Centre;
use App\Services\CentreProvisioner;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Yangi o'quv markazini ochish.
 *
 * Shu paytgacha markaz ochishning qo'llab-quvvatlanadigan yo'li umuman
 * yo'q edi — faqat seeder yoki bazaga qo'lda INSERT, ya'ni kutish zalisiz
 * va SMS shablonlarisiz nosoz markaz.
 */
class CentreCreate extends Command
{
    protected $signature = 'centre:create
                            {--slug= : subdomen (masalan beta -> beta.domen.uz)}
                            {--name= : markazning ko‘rinadigan nomi}
                            {--prefix= : sertifikat seriyasi prefiksi (masalan BET)}
                            {--timezone= : vaqt mintaqasi}
                            {--admin-name= : admin ismi}
                            {--admin-phone= : admin telefoni}
                            {--admin-email= : admin pochtasi (ixtiyoriy)}
                            {--admin-password= : admin paroli}
                            {--no-admin : adminsiz ochish (keyinroq biriktiriladi)}';

    protected $description = 'Yangi o‘quv markazini ochadi: markaz, kutish zali, SMS shablonlari va admin';

    public function handle(CentreProvisioner $provisioner): int
    {
        $slug = $this->option('slug') ?: $this->ask('Subdomen (slug), masalan: beta');

        if ($problem = $provisioner->slugProblem((string) $slug)) {
            $this->error($problem);

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Markaz nomi');

        $admin = null;

        if (! $this->option('no-admin')) {
            $admin = [
                'name'     => $this->option('admin-name') ?: $this->ask('Admin ismi', 'Administrator'),
                'phone'    => $this->option('admin-phone') ?: $this->ask('Admin telefoni (998XXXXXXXXX)'),
                'email'    => $this->option('admin-email') ?: null,
                'password' => $this->option('admin-password') ?: $this->secret('Admin paroli (kamida 6 belgi)'),
            ];
        }

        try {
            $centre = $provisioner->create([
                'slug'               => (string) $slug,
                'name'               => (string) $name,
                'certificate_prefix' => $this->option('prefix'),
                'timezone'           => $this->option('timezone'),
                'admin'              => $admin,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("«{$centre->name}» ochildi.");

        $this->table(['', ''], [
            ['id', $centre->id],
            ['slug', $centre->slug],
            ['manzil', $centre->url()],
            ['kutish zali', 'guruh #' . $centre->waiting_room_group_id],
            ['sertifikat prefiksi', $centre->certificate_prefix],
            ['vaqt mintaqasi', $centre->timezone],
            ['admin', $admin ? $admin['name'] . ' (' . $admin['phone'] . ')' : '— biriktirilmadi'],
        ]);

        $this->newLine();
        $this->line('  DNS da wildcard yozuvi borligiga ishonch hosil qiling: '
            . '<comment>*.' . config('app.domain') . '</comment>');

        if (filled(config('app.default_centre'))) {
            $this->warn('  APP_DEFAULT_CENTRE = «' . config('app.default_centre') . '» — '
                . 'markaz nomlanmagan host hamon o‘sha markazga tushadi. '
                . 'Haqiqiy subdomenlarga o‘tgach uni bo‘shating.');
        }

        return self::SUCCESS;
    }
}
