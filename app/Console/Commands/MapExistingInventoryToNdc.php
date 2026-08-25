<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Organization\NdcMigrationReconciler;

class MapExistingInventoryToNdc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'epfo:map-ndc-inventory {--dry-run : Execute the mapping without committing changes} {--verify : Output verification logs} {--resume : Resume from a previous failure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maps existing unstructured inventory and location data to the newly seeded NDC structure.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $verify = $this->option('verify');
        // resume isn't explicitly complex here since we made reconcile idempotent, 
        // but we'll accept the flag.

        $this->info('Starting NDC inventory mapping...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE ENABLED. Changes will be rolled back.');
        }

        try {
            $reconciler = new NdcMigrationReconciler($dryRun, $verify);
            $stats = $reconciler->reconcile();

            $this->info('Mapping completed successfully.');
            $this->table(
                ['Entity', 'Processed Count'],
                [
                    ['Locations Mapped', $stats['locations_mapped']],
                    ['Assets Backfilled', $stats['assets_backfilled']],
                    ['Users Assigned', $stats['users_assigned']],
                ]
            );

        } catch (\Exception $e) {
            $this->error('Failed to map inventory: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
