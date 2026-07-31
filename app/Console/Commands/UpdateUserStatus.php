<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPerCentre;
use App\Models\Centre;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Advances every student's paid-months counter by one month.
 *
 * `users.status` is a counter, not a state: a negative value means "owes N
 * months", and every debt screen reads it. Scheduled monthly.
 */
class UpdateUserStatus extends Command
{
    use RunsPerCentre;

    protected $signature = 'user:status:update
                            {--centre= : faqat shu markaz (slug)}';

    protected $description = 'Har bir talabaning to‘langan oylar hisoblagichini bir oyga kamaytiradi';

    public function handle()
    {
        return $this->eachCentre(function (Centre $centre) {
            // Centre-scoped for free: with Spatie teams on, role() only matches
            // assignments belonging to the current centre.
            $affected = User::role('student')->decrement('status');

            $this->info("  {$affected} ta talaba yangilandi.");
        });
    }
}
