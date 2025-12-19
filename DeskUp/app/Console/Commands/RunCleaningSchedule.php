<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CleaningService;

class RunCleaningSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleaning:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run recurring cleaning schedule';

    /**
     * Execute the console command.
     */
    public function handle(CleaningService $service)
    {
        $service->runCleaningSchedule();
        return self::SUCCESS;
    }
}
