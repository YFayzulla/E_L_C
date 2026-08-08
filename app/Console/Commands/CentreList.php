<?php

namespace App\Console\Commands;

use App\Models\Centre;
use App\Models\User;
use App\Tenancy\CentreContext;
use Illuminate\Console\Command;

/**
 * Qaysi markazlar bor, qaysi holatda va har birida nechta odam.
 */
class CentreList extends Command
{
    protected $signature = 'centre:list {--all : arxivlanganlarni ham ko‘rsatish}';

    protected $description = 'O‘quv markazlari ro‘yxati';

    public function handle(CentreContext $context): int
    {
        $centres = Centre::query()
            ->when(! $this->option('all'), fn ($q) => $q->where('status', '!=', Centre::STATUS_ARCHIVED))
            ->orderBy('id')
            ->get();

        if ($centres->isEmpty()) {
            $this->warn('Bironta ham markaz yo‘q. `php artisan centre:create` bilan oching.');

            return self::SUCCESS;
        }

        $rows = $centres->map(function (Centre $centre) use ($context) {
            // Rol biriktirishlar markazga bog'langan (Spatie teams), shuning
            // uchun sanoq har bir markazning o'z kontekstida olinishi kerak.
            $counts = $context->for($centre, fn () => [
                'admin'   => User::role('admin')->count(),
                'teacher' => User::role('user')->count(),
                'student' => User::role('student')->count(),
            ]);

            return [
                $centre->id,
                $centre->slug,
                $centre->name,
                $centre->statusLabel(),
                $counts['admin'],
                $counts['teacher'],
                $counts['student'],
                $centre->waiting_room_group_id ?: '⚠ yo‘q',
                $centre->host(),
            ];
        })->all();

        $this->table(
            ['#', 'slug', 'nom', 'holat', 'admin', 'o‘qit.', 'talaba', 'kutish zali', 'manzil'],
            $rows
        );

        $broken = $centres->whereNull('waiting_room_group_id');

        if ($broken->isNotEmpty()) {
            $this->warn('  Kutish zalisiz markaz(lar) bor — talaba qabul qilish ishlamaydi: '
                . $broken->pluck('slug')->implode(', '));
        }

        return self::SUCCESS;
    }
}
