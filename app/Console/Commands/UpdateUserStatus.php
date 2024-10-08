<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:status:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user status';

    /**
     * Execute the console command.
     *
     * @return
     */
    public function handle()
    {
        // Decrement the status column for all users
        User::query()->role('student')->where('group_id' ,'!=', 1)->decrement('status');
        $this->info('User status updated successfully.');
//        return Command::SUCCESS;
    }
}
