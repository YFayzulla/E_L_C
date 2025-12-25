<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MessageService;
use Illuminate\Console\Command;

class SendSms extends Command
{
    private MessageService $service;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->service = $messageService;
    }

    protected $signature = 'user:sms:send';

    protected $description = 'Command description';

    public function handle()
    {
        $message = 'Это тест от Eskiz';

        $students = User::query()
            ->role('student')
            ->whereNotNull('status')
            ->where('status', '<=', 0)
            ->get();

        foreach ($students as $student) {

            $this->service->sendMessage($student->phone, $message);

        }

        return Command::SUCCESS;
    }
}
