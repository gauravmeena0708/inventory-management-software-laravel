<?php

namespace App\Console\Commands;

use App\Services\Importer\LegacyImportManager;
use App\Services\Importer\ReconciliationReporter;
use Illuminate\Console\Command;

class ImportLegacyInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:import-legacy
                            {--dry-run : Simulate the import without committing changes}
                            {--resume : Resume from previous run}
                            {--verify : Run reconciliation verification only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and reconcile legacy inventory database into the modernized schema';

    /**
     * Execute the console command.
     */
    public function handle(LegacyImportManager $manager, ReconciliationReporter $reporter): int
    {
        $isVerifyOnly = (bool) $this->option('verify');
        $isDryRun = (bool) $this->option('dry-run');
        $isResume = (bool) $this->option('resume');

        if ($isVerifyOnly) {
            return $this->handleVerification($reporter);
        }

        return $this->handleImport($manager, $reporter, $isDryRun, $isResume);
    }

    /**
     * Handle verification-only mode.
     */
    protected function handleVerification(ReconciliationReporter $reporter): int
    {
        $this->info('====================================================');
        $this->info('  Legacy Inventory Data Reconciliation Verification ');
        $this->info('====================================================');

        $report = $reporter->generateReport();

        $this->renderCountsTable($report['counts']['entities']);
        $this->renderFinancialParityTable($report['financial_parity']);

        if (!empty($report['stock_discrepancies'])) {
            $this->warn("\n[!] Stock Ledger Discrepancies Detected:");
            $stockRows = array_map(fn ($d) => [
                $d['name'],
                $d['in_stock'],
                $d['latest_entry_stock'],
                $d['difference'],
            ], $report['stock_discrepancies']);

            $this->table(['Consumable', 'in_stock Value', 'Latest Entry stock_after', 'Difference'], $stockRows);
        }

        if (!empty($report['unmapped'])) {
            $this->warn("\n[!] Unmapped Legacy Records Detected:");
            foreach ($report['unmapped'] as $table => $ids) {
                $this->line(" - Table '{$table}': " . count($ids) . " unmapped IDs: " . implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : ''));
            }
        }

        if ($report['is_clean']) {
            $this->newLine();
            $this->info(' [✓] Verification PASSED: Zero data loss, complete count parity, and financial balance verified.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(' [!] Verification completed with recorded discrepancies or orphaned references.');
        return self::SUCCESS;
    }

    /**
     * Handle the data import process.
     */
    protected function handleImport(
        LegacyImportManager $manager,
        ReconciliationReporter $reporter,
        bool $isDryRun,
        bool $isResume
    ): int {
        $modeLabel = $isDryRun ? 'DRY-RUN (Simulated, No DB Writes)' : 'LIVE (Committing Changes)';
        $resumeLabel = $isResume ? 'YES' : 'NO';

        $this->info('====================================================');
        $this->info('       Legacy Inventory Data Modernization Importer ');
        $this->info('====================================================');
        $this->line(" Mode:   <comment>{$modeLabel}</comment>");
        $this->line(" Resume: <comment>{$resumeLabel}</comment>\n");

        $this->info('Starting extraction and transformation pipeline...');

        $run = $manager->import(
            dryRun: $isDryRun,
            resume: $isResume,
            progressCallback: function (string $stage, int $processed, int $total) {
                $this->line(" -> [{$stage}] Processed {$processed} / {$total} rows");
            }
        );

        $this->newLine();
        $this->info("====================================================");
        $this->info("             Import Execution Summary               ");
        $this->info("====================================================");

        $countRows = [];
        foreach ($run->counts ?? [] as $entity => $count) {
            $countRows[] = [$entity, $count];
        }

        $this->table(['Entity / Table', 'Imported Count'], $countRows);

        $anomalies = $run->anomalies ?? [];
        if (!empty($anomalies)) {
            $this->warn("\n[!] Recorded Anomalies During Import (" . count($anomalies) . "):");
            $anomalyRows = array_slice(array_map(fn ($a) => [
                $a['table'] ?? 'N/A',
                $a['type'] ?? 'N/A',
                $a['message'] ?? 'N/A',
            ], $anomalies), 0, 15);

            $this->table(['Source Table', 'Anomaly Type', 'Detail'], $anomalyRows);
            if (count($anomalies) > 15) {
                $this->line(" ... and " . (count($anomalies) - 15) . " more anomalies (see LegacyImportRun report).");
            }
        } else {
            $this->info(" [✓] Zero anomalies recorded during import pipeline.");
        }

        $this->newLine();
        $this->info("Running post-import reconciliation verification...");
        $verifyReport = $reporter->generateReport();
        $this->renderCountsTable($verifyReport['counts']['entities']);
        $this->renderFinancialParityTable($verifyReport['financial_parity']);

        if ($isDryRun) {
            $this->newLine();
            $this->comment(" [i] Dry-run simulation completed successfully. Target database remains clean and untouched.");
        } else {
            $this->newLine();
            $this->info(" [✓] Legacy inventory data import completed successfully.");
        }

        return self::SUCCESS;
    }

    /**
     * Render entity counts table in console.
     */
    protected function renderCountsTable(array $entities): void
    {
        $rows = [];
        foreach ($entities as $entity => $data) {
            $status = $data['match'] ? '<info>MATCH</info>' : '<error>MISMATCH</error>';
            $diffStr = $data['difference'] > 0 ? "+{$data['difference']}" : (string) $data['difference'];

            $rows[] = [
                ucwords(str_replace('_', ' ', $entity)),
                $data['legacy'],
                $data['target'],
                $diffStr,
                $status,
            ];
        }

        $this->table(['Entity', 'Legacy Source Count', 'Target Modernized Count', 'Difference', 'Status'], $rows);
    }

    /**
     * Render financial parity table in console.
     */
    protected function renderFinancialParityTable(array $parity): void
    {
        $rows = [
            [
                'Agreements Annual Cost Total',
                number_format($parity['legacy_agreements_total'], 2),
                number_format($parity['target_agreements_total'], 2),
                number_format($parity['agreements_diff'], 2),
                abs($parity['agreements_diff']) < 0.01 ? '<info>MATCH</info>' : '<error>MISMATCH</error>',
            ],
            [
                'Payments Total Amount',
                number_format($parity['legacy_payments_total'], 2),
                number_format($parity['target_payments_total'], 2),
                number_format($parity['payments_diff'], 2),
                abs($parity['payments_diff']) < 0.01 ? '<info>MATCH</info>' : '<error>MISMATCH</error>',
            ],
        ];

        $this->newLine();
        $this->table(['Financial Parity Check', 'Legacy Total (INR)', 'Target Total (INR)', 'Difference', 'Status'], $rows);
    }
}
