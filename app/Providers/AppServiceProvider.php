<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // The UI is Bootstrap 5; the Tailwind bundle is not built.
        Paginator::useBootstrapFive();

        // Month / weekday names in dates rendered with translatedFormat().
        // "uz" alone resolves to the Cyrillic script — be explicit about Latin.
        Carbon::setLocale('uz_Latn');
    }
}
