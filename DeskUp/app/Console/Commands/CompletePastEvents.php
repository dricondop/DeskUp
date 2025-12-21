<?php

namespace App\Console\Commands;

use App\Services\CleaningService;
use Illuminate\Console\Command;

class CompletePastEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:complete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change events status to "completed"';

    /**
     * Execute the console command.
     */
    public function handle(CleaningService $service)
    {
        $service->markPastEventsAsComplete();
        return self::SUCCESS;
    }
}
