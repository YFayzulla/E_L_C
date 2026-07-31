<?php

namespace App\Console\Commands\Concerns;

use App\Models\Centre;
use App\Tenancy\CentreContext;

/**
 * Runs a command's body once per centre.
 *
 * Every console command needs this, and the reason is easy to miss: with
 * Spatie's team feature on, a process that never sets a team id sees
 * `model_has_roles.centre_id = NULL`, which matches no row. `User::role('student')`
 * then returns nobody, the command does nothing, and it reports success. A
 * monthly billing job could run that way for a year unnoticed.
 *
 * Add `--centre=` to the signature to let an operator target one.
 */
trait RunsPerCentre
{
    protected function eachCentre(callable $callback): int
    {
        $only = $this->hasOption('centre') ? $this->option('centre') : null;

        if ($only !== null && $only !== '') {
            $centre = Centre::withoutGlobalScopes()->where('slug', $only)->first();

            if ($centre === null) {
                $this->error("«{$only}» nomli o‘quv markazi topilmadi.");

                return self::FAILURE;
            }

            app(CentreContext::class)->for($centre, fn() => $callback($centre));

            return self::SUCCESS;
        }

        $centres = Centre::withoutGlobalScopes()
            ->where('status', Centre::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();

        if ($centres->isEmpty()) {
            $this->warn('Faol o‘quv markazi yo‘q — hech narsa qilinmadi.');

            return self::SUCCESS;
        }

        foreach ($centres as $centre) {
            $this->line("→ {$centre->name} ({$centre->slug})");

            app(CentreContext::class)->for($centre, fn() => $callback($centre));
        }

        return self::SUCCESS;
    }
}
