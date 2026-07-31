<?php

namespace Database\Seeders;

use App\Models\Centre;
use Illuminate\Database\Seeder;

/**
 * The first o'quv markazi.
 *
 * The backfill migration already creates it on an existing installation; this
 * covers `migrate:fresh --seed`, where there is nothing to back fill.
 * Idempotent either way.
 */
class CentreSeeder extends Seeder
{
    public function run()
    {
        $slug = env('CENTRE_SLUG', 'alpha');

        $centre = Centre::withoutGlobalScopes()->where('slug', $slug)->first();

        if ($centre === null) {
            $centre = new Centre();
            $centre->forceFill([
                'slug'               => $slug,
                'name'               => env('APP_NAME', 'ALPHA'),
                'status'             => Centre::STATUS_ACTIVE,
                'timezone'           => config('app.timezone', 'Asia/Tashkent'),
                'certificate_prefix' => env('CERTIFICATE_PREFIX', 'ALP'),
                'sms_enabled'        => true,
            ])->save();
        }

        $this->command?->info(
            ($centre->wasRecentlyCreated ? 'Markaz yaratildi' : 'Markaz allaqachon bor')
            . " — {$centre->name} ({$centre->slug}), id {$centre->id}"
        );
    }
}
