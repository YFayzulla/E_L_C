<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MessageService;
use Illuminate\Console\Command;

/**
 * Verifies the Eskiz setup without guesswork:
 *
 *   php artisan sms:check                  — config + auth + balance, sends nothing
 *   php artisan sms:check --to=901234567   — also sends one real message
 */
class SmsCheck extends Command
{
    protected $signature = 'sms:check
                            {--to= : Send a real test SMS to this number}
                            {--message= : Text for the test SMS}';

    protected $description = 'Eskiz SMS sozlamalarini tekshirish';

    public function handle(MessageService $sms): int
    {
        $this->newLine();
        $this->line('  <options=bold>Eskiz SMS — tekshiruv</>');
        $this->newLine();

        // ---------------------------------------------------------- config
        $rows = [
            ['ESKIZ_EMAIL', filled(config('eskiz.email')) ? '<info>bor</info>' : '<error>YO\'Q</error>'],
            ['ESKIZ_PASSWORD', filled(config('eskiz.password')) ? '<info>bor</info>' : '<error>YO\'Q</error>'],
            ['ESKIZ_FROM', (string) config('eskiz.from')],
            ['ESKIZ_ENABLED', config('eskiz.enabled') ? 'true' : '<comment>false</comment>'],
            ['ESKIZ_DRY_RUN', config('eskiz.dry_run') ? '<comment>true</comment>' : 'false'],
            ['Manzil', (string) config('eskiz.base_url')],
            ['Callback', config('eskiz.callback_url') ?: '—'],
        ];

        $this->table(['Sozlama', 'Qiymat'], $rows);

        $status = $sms->status();
        $this->line("  Holat: <options=bold>{$status['title']}</> — {$status['detail']}");
        $this->newLine();

        if (! $sms->isConfigured()) {
            $this->error('  .env faylga ESKIZ_EMAIL va ESKIZ_PASSWORD qo\'shing, keyin qayta ishga tushiring.');
            $this->line('  Shundan keyingina SMS yuboriladi — hozir hech qanday so\'rov jo\'natilmaydi.');
            $this->newLine();

            return self::FAILURE;
        }

        // ---------------------------------------------------------- auth
        $this->line('  Eskizga ulanmoqda...');

        if (! $sms->getToken(true)) {
            $this->error('  Ulanib bo\'lmadi — email yoki parol noto\'g\'ri. Tafsilotlar: storage/logs/laravel.log');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('  Ulandi, token olindi.');

        $balance = $sms->balance();
        $this->line('  Balans: ' . ($balance === null ? '<comment>o\'qib bo\'lmadi</comment>' : "<info>{$balance}</info> SMS"));
        $this->newLine();

        // ---------------------------------------------------------- test send
        $to = $this->option('to');

        if (! $to) {
            $this->line('  Haqiqiy SMS yuborib ko\'rish uchun: <options=bold>php artisan sms:check --to=901234567</>');
            $this->newLine();

            return self::SUCCESS;
        }

        $phone = User::normalizePhone($to);

        if (! $phone) {
            $this->error("  Telefon raqami noto'g'ri: {$to}");

            return self::FAILURE;
        }

        $text = $this->option('message')
            ?: config('app.name') . ' — sinov xabari. ' . now()->format('d.m.Y H:i');

        if (! $this->confirm("  +{$phone} raqamiga haqiqiy SMS yuborilsinmi?", false)) {
            $this->line('  Bekor qilindi.');

            return self::SUCCESS;
        }

        $result = $sms->send($phone, $text);

        if ($result['sent']) {
            $this->info("  Yuborildi. Xabar id: " . ($result['id'] ?: '—'));
            $this->newLine();

            return self::SUCCESS;
        }

        $this->error('  Yuborilmadi — ' . MessageService::reasonLabel($result['reason']));
        $this->line('  Tafsilotlar: storage/logs/laravel.log');
        $this->newLine();

        return self::FAILURE;
    }
}
