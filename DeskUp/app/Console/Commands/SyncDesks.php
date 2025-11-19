<?php

namespace App\Console\Commands;

use App\Services\DeskSyncService;
use Illuminate\Console\Command;

class SyncDesks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'desks:sync {--force : Force sync even if API is unavailable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize desks from the API simulator to the database';

    /**
     * Execute the console command.
     */
    public function handle(DeskSyncService $syncService)
    {
        $this->info('Starting desk synchronization...');
        $this->newLine();

        // Check API availability
        if (!$syncService->isAPIAvailable() && !$this->option('force')) {
            $this->error('API is not available. Use --force to skip this check.');
            return Command::FAILURE;
        }

        try {
            // Perform sync
            $results = $syncService->syncFromAPI();

            // Display results
            $this->info("✓ Synchronization completed!");
            $this->newLine();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total API Desks', $results['total_api_desks']],
                    ['Created', $results['created']],
                    ['Updated', $results['updated']],
                    ['Errors', count($results['errors'])]
                ]
            );

            // Display errors if any
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->warn('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->line("  • {$error}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Synchronization failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
