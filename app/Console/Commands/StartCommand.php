<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'start sms sending';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Schedule $schedule)
    {
//        info("Cron Job running at ". now());
//        info('start ishladi');
        $schedule->command('sms:send')->everyMinute();
//        info('command');
    }
}
