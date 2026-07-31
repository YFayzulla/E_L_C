<?php

namespace App\Providers;

use App\Tenancy\CentreContext;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        // One per process: the middleware sets it, the global scope reads it,
        // the console loops swap it. Anything else would let two parts of a
        // request disagree about which centre they are in.
        $this->app->singleton(CentreContext::class);
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

        // The platform owner passes every gate. Deliberately NOT a Spatie role:
        // with teams on, a role assignment must belong to a centre, and the
        // super-admin belongs to none.
        Gate::before(function ($user) {
            return $user->is_super_admin ? true : null;
        });
    }
}
