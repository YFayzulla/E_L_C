<?php

namespace App\Console\Commands;

use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Bulk reminder to the guardians of students who owe money.
 *
 * Replaces the previous version, which sent a hardcoded Russian test string to
 * the STUDENT's own number.
 *
 *   php artisan sms:debtors --dry     — list who would be messaged, send nothing
 *   php artisan sms:debtors           — ask first, then send
 *   php artisan sms:debtors --force   — no prompt (for cron)
 */
class SendSms extends Command
{
    protected $signature = 'sms:debtors
                            {--template=payment-reminder : Slug of the SMS template to use}
                            {--to=ota,ona : Who receives it — ota, ona, vasiy, student}
                            {--dry : Show the recipients and the text, send nothing}
                            {--force : Do not ask for confirmation}';

    protected $description = "Qarzdor talabalarning ota-onasiga to'lov eslatmasi yuborish";

    public function __construct(private MessageService $sms)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $status = $this->sms->status();
        $this->newLine();
        $this->line("  Eskiz holati: <options=bold>{$status['title']}</> — {$status['detail']}");

        if (! $this->sms->isConfigured() && ! $this->option('dry')) {
            $this->newLine();
            $this->error('  Eskiz sozlanmagan — .env ga ESKIZ_EMAIL va ESKIZ_PASSWORD qo\'shing.');
            $this->line('  Kimga ketishini ko\'rish uchun: php artisan sms:debtors --dry');
            $this->newLine();

            return self::FAILURE;
        }

        $template = SmsTemplate::where('slug', $this->option('template'))->first();

        if (! $template) {
            $this->error('  Shablon topilmadi: ' . $this->option('template'));
            $this->line('  Mavjudlari: ' . SmsTemplate::pluck('slug')->implode(', '));

            return self::FAILURE;
        }

        $audiences = array_values(array_filter(
            array_map('trim', explode(',', (string) $this->option('to'))),
            fn($a) => in_array($a, MessageService::AUDIENCES, true)
        ));

        if (empty($audiences)) {
            $this->error('  --to noto\'g\'ri. Ruxsat etilgan: ' . implode(', ', MessageService::AUDIENCES));

            return self::FAILURE;
        }

        // Only students who are actually studying (billable() drops the Kutish
        // zali and graduates) and actually owe money.
        $debtors = User::role('student')
            ->billable()
            ->where('status', '<', 0)
            ->with(['groups', 'deptStudent'])
            ->orderBy('name')
            ->get();

        if ($debtors->isEmpty()) {
            $this->newLine();
            $this->info('  Qarzdor talaba yo\'q.');
            $this->newLine();

            return self::SUCCESS;
        }

        $plan = [];

        foreach ($debtors as $student) {
            $recipients = $this->sms->recipientsFor($student, $audiences);

            if (empty($recipients)) {
                $plan[] = [$student->name, '—', 'raqam yo\'q', ''];
                continue;
            }

            $text = $template->render(SmsTemplate::contextForStudent($student));

            foreach ($recipients as $recipient) {
                $plan[] = [
                    $student->name,
                    $recipient['label'],
                    '+' . $recipient['phone'],
                    Str::limit($text, 55),
                ];
            }
        }

        $this->newLine();
        $this->table(['Talaba', 'Kimga', 'Raqam', 'Matn'], $plan);

        $sendable = collect($plan)->filter(fn($r) => str_starts_with($r[2], '+'))->count();
        $this->line("  Jami: {$debtors->count()} ta qarzdor, {$sendable} ta xabar.");
        $this->newLine();

        if ($this->option('dry')) {
            $this->comment('  --dry rejimi: hech narsa yuborilmadi.');
            $this->newLine();

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("  {$sendable} ta SMS yuborilsinmi?", false)) {
            $this->line('  Bekor qilindi.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($debtors as $student) {
            $text = $template->render(SmsTemplate::contextForStudent($student));
            $result = $this->sms->sendAboutStudent($student, $text, $audiences);

            $sent += $result['sent'];
            $failed += $result['skipped'];
        }

        $this->newLine();
        $this->info("  Yuborildi: {$sent}" . ($failed ? ",  yuborilmadi: {$failed}" : ''));
        $this->line('  Tafsilotlar: storage/logs/laravel.log');
        $this->newLine();

        return self::SUCCESS;
    }
}
