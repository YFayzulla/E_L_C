<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Chegirmalar oylik hisobdan OLDIN yangilanadi: 1-sanada boshlanadigan
        // yoki tugaydigan chegirma shu oyning summasiga to'g'ri tushsin.
        $schedule->command('discounts:refresh')
             ->dailyAt('00:10')
             ->timezone('Asia/Tashkent');

        $schedule->command('user:status:update')
             ->monthlyOn(1, '07:00')
             ->timezone('Asia/Tashkent');

    }

    /**
     *
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}