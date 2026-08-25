<?php

namespace App\Console\Commands;

use App\Services\Organization\NdcMigrationReconciler;
use Illuminate\Console\Command;

class MapExistingInventoryToNdc extends Command
{
    protected $signature = 'epfo:map-ndc-inventory
                            {--apply : Apply the mapping (the backward-compatible default)}
                            {--dry-run : Report the changes and roll them back}
                            {--verify : Verify mapping without writing}
                            {--resume : Continue a partially completed mapping}';

    protected $description = 'Map and reconcile existing inventory against the seeded NDC hierarchy.';

    public function handle(): int
    {
        $selectedModes = collect(['apply', 'dry-run', 'verify', 'resume'])
            ->filter(fn (string $mode): bool => (bool) $this->option($mode));

        if ($selectedModes->count() > 1) {
            $this->error('Choose only one of --apply, --dry-run, --verify, or --resume.');

            return self::INVALID;
        }

        if ($this->option('verify')) {
            return $this->verify();
        }

        $dryRun = (bool) $this->option('dry-run');
        $resume = (bool) $this->option('resume');
        $mode = $dryRun ? 'DRY RUN' : ($resume ? 'RESUME' : 'APPLY');

        $this->info("Starting NDC inventory mapping in {$mode} mode...");

        if ($dryRun) {
            $this->warn('All target changes will be rolled back.');
        } elseif ($resume) {
            $this->comment('Only unresolved records and incorrect memberships will be processed.');
        }

        try {
            $stats = (new NdcMigrationReconciler($dryRun, $resume))->reconcile();

            $this->table(
                ['Entity', 'Processed Count'],
                [
                    ['Locations mapped', $stats['locations_mapped']],
                    ['Location paths rebuilt', $stats['location_paths_rebuilt']],
                    ['Assets backfilled', $stats['assets_backfilled']],
                    ['Users assigned or corrected', $stats['users_assigned']],
                    ['Users already complete', $stats['users_skipped']],
                    ['Stock balances created', $stats['stock_balances_created']],
                    ['Legacy stock entries copied', $stats['stock_entries_copied']],
                    ['Legacy stock discrepancies', $stats['stock_discrepancies']],
                    ['Stock migration conflicts', $stats['stock_conflicts']],
                ]
            );
            $this->info("NDC {$stats['mode']} completed successfully.");
        } catch (\Throwable $exception) {
            $this->error('Failed to map inventory: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function verify(): int
    {
        $this->info('Verifying NDC inventory mapping (read-only)...');

        $report = (new NdcMigrationReconciler)->verify();
        $this->table(
            ['Check', 'Outstanding', 'Record IDs / keys'],
            [
                ['Baseline records', count($report['baseline_missing']), implode(', ', $report['baseline_missing']) ?: '-'],
                ['Locations without a site', count($report['unmapped_locations']), implode(', ', $report['unmapped_locations']) ?: '-'],
                ['Assets without ownership', count($report['unmapped_assets']), implode(', ', $report['unmapped_assets']) ?: '-'],
                ['Users without NDC context', count($report['unmapped_users']), implode(', ', $report['unmapped_users']) ?: '-'],
                ['Invalid location paths', count($report['invalid_location_paths']), implode(', ', $report['invalid_location_paths']) ?: '-'],
                ['Consumables without NDC store balance', count($report['unmapped_consumable_stock']), implode(', ', $report['unmapped_consumable_stock']) ?: '-'],
                ['Legacy entries not copied', count($report['unmigrated_legacy_entries']), implode(', ', $report['unmigrated_legacy_entries']) ?: '-'],
                ['Aggregate stock discrepancies', count($report['stock_balance_discrepancies']), implode(', ', $report['stock_balance_discrepancies']) ?: '-'],
            ]
        );

        if (! $report['is_clean']) {
            $this->error('Verification failed: unresolved NDC mapping records remain.');

            return self::FAILURE;
        }

        $this->info('Verification passed: the NDC mapping is complete and internally consistent.');

        return self::SUCCESS;
    }
}
