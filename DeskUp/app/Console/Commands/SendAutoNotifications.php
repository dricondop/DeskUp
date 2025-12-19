<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class SendAutoNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check users sitting time and send automatic notifications';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for users that need notifications...');

        $count = $this->notificationService->checkAndSendAutoNotifications();

        if ($count > 0) {
            $this->info("Sent {$count} automatic notification(s).");
        } else {
            $this->info('No notifications needed at this time.');
        }

        return Command::SUCCESS;
    }
}
