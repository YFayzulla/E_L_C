<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPerCentre;
use App\Models\Centre;
use App\Services\DiscountService;
use Illuminate\Console\Command;

/**
 * Muddatli chegirmalar: boshlangan kuni qo'llanadi, tugagan kuni olib
 * tashlanadi — oylik summa (group_user.payment, should_pay, dept) qayta
 * hisoblanadi. Har kuni ishlaydi.
 */
class RefreshDiscounts extends Command
{
    use RunsPerCentre;

    protected $signature = 'discounts:refresh
                            {--centre= : faqat shu markaz (slug)}';

    protected $description = 'Muddati boshlangan yoki tugagan chegirmalar bo‘yicha oylik summani qayta hisoblaydi';

    public function handle(DiscountService $discounts)
    {
        return $this->eachCentre(function (Centre $centre) use ($discounts) {
            $count = $discounts->refreshDue();

            $this->info("  {$count} ta talaba qayta hisoblandi.");
        });
    }
}
