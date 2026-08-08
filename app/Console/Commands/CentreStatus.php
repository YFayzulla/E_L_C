<?php

namespace App\Console\Commands;

use App\Models\Centre;
use Illuminate\Console\Command;

/**
 * Markazni to'xtatish / qayta ochish / arxivlash.
 *
 * To'xtatilgan markaz 503 qaytaradi (ma'lumot joyida, kirish yopiq),
 * arxivlangan esa 404 — ya'ni tashqaridan u umuman mavjud emasday.
 * Ma'lumot hech qaysi holatda o'chirilmaydi.
 */
class CentreStatus extends Command
{
    protected $signature = 'centre:status
                            {slug : markaz slug‘i}
                            {state : faol|toxtatilgan|arxiv}';

    protected $description = 'Markaz holatini o‘zgartiradi (faol / to‘xtatilgan / arxiv)';

    private const STATES = [
        'faol'         => Centre::STATUS_ACTIVE,
        'active'       => Centre::STATUS_ACTIVE,
        'toxtatilgan'  => Centre::STATUS_SUSPENDED,
        'suspended'    => Centre::STATUS_SUSPENDED,
        'arxiv'        => Centre::STATUS_ARCHIVED,
        'archived'     => Centre::STATUS_ARCHIVED,
    ];

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $state = mb_strtolower((string) $this->argument('state'));

        if (! array_key_exists($state, self::STATES)) {
            $this->error('Holat noma’lum. Mumkin: faol, toxtatilgan, arxiv.');

            return self::FAILURE;
        }

        $centre = Centre::where('slug', $slug)->first();

        if ($centre === null) {
            $this->error("«{$slug}» nomli markaz topilmadi.");

            return self::FAILURE;
        }

        $was = $centre->statusLabel();
        // saved() hodisasi ResolveCentre keshini o'zi tozalaydi, ya'ni
        // to'xtatish darhol kuchga kiradi.
        $centre->update(['status' => self::STATES[$state]]);

        $this->info("«{$centre->name}»: {$was} → {$centre->statusLabel()}");

        return self::SUCCESS;
    }
}
