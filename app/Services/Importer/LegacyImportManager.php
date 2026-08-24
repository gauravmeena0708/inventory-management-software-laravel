<?php

namespace App\Services\Importer;

use App\Models\LegacyImportRun;
use App\Services\Importer\TableImporters\AgreementTableImporter;
use App\Services\Importer\TableImporters\AssetTableImporter;
use App\Services\Importer\TableImporters\ConsumableTableImporter;
use App\Services\Importer\TableImporters\MasterDataTableImporter;
use App\Services\Importer\TableImporters\PersonnelTableImporter;
use App\Services\Importer\TableImporters\TaskTableImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyImportManager
{
    /**
     * Legacy connection name.
     */
    protected string $legacyConnection = 'legacy';

    /**
     * Target connection name (null uses default).
     */
    protected ?string $targetConnection = null;

    /**
     * Chunk size for batch operations.
     */
    protected int $chunkSize = 500;

    /**
     * Reconciliation reporter instance.
     */
    protected ReconciliationReporter $reporter;

    /**
     * Create a new legacy import manager instance.
     */
    public function __construct(?ReconciliationReporter $reporter = null)
    {
        $this->reporter = $reporter ?? new ReconciliationReporter();
    }

    /**
     * Set the legacy connection name.
     */
    public function setLegacyConnection(string $connection): self
    {
        $this->legacyConnection = $connection;
        return $this;
    }

    /**
     * Set the target connection name.
     */
    public function setTargetConnection(?string $connection): self
    {
        $this->targetConnection = $connection;
        return $this;
    }

    /**
     * Set the chunk size for batch processing.
     */
    public function setChunkSize(int $chunkSize): self
    {
        $this->chunkSize = $chunkSize;
        return $this;
    }

    /**
     * Run the complete legacy inventory import routine.
     *
     * @param bool $dryRun Whether to simulate the import without persisting changes to the target database
     * @param bool $resume Whether to resume from a previous checkpoint
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return LegacyImportRun
     */
    public function import(
        bool $dryRun = false,
        bool $resume = false,
        ?callable $progressCallback = null
    ): LegacyImportRun {
        $startedAt = now();
        $fingerprint = $this->calculateSourceFingerprint();

        $allCounts = [];
        $allAnomalies = [];

        // Wrap execution in transaction if dry-run
        if ($dryRun) {
            DB::connection($this->targetConnection)->beginTransaction();
        }

        try {
            // Stage 1: Master Data (Locations, Manufacturers, Devcats, Files)
            $masterImporter = new MasterDataTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $masterCounts = $masterImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $masterCounts);
            $allAnomalies = array_merge($allAnomalies, $masterImporter->getAnomalies());

            // Stage 2: Personnel (Users, Officials, Developers)
            $personnelImporter = new PersonnelTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $personnelCounts = $personnelImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $personnelCounts);
            $allAnomalies = array_merge($allAnomalies, $personnelImporter->getAnomalies());

            // Stage 3: Unified Assets (Desktops, Laptops, Servers, Devices, Storages + Assignments)
            $assetImporter = new AssetTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $assetCounts = $assetImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $assetCounts);
            $allAnomalies = array_merge($allAnomalies, $assetImporter->getAnomalies());

            // Stage 4: Consumables & Stock Entries (Consumables, Entries, Stock verification)
            $consumableImporter = new ConsumableTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $consumableCounts = $consumableImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $consumableCounts);
            $allAnomalies = array_merge($allAnomalies, $consumableImporter->getAnomalies());

            // Stage 5: Agreements & Payments (Agreements, Payments)
            $agreementImporter = new AgreementTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $agreementCounts = $agreementImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $agreementCounts);
            $allAnomalies = array_merge($allAnomalies, $agreementImporter->getAnomalies());

            // Stage 6: Tasks
            $taskImporter = new TaskTableImporter(
                $this->legacyConnection,
                $this->targetConnection,
                $dryRun,
                $this->chunkSize
            );
            $taskCounts = $taskImporter->import($progressCallback);
            $allCounts = array_merge($allCounts, $taskCounts);
            $allAnomalies = array_merge($allAnomalies, $taskImporter->getAnomalies());

            $completedAt = now();
            $status = $dryRun ? 'dry_run_completed' : 'completed';

            if ($dryRun) {
                // Roll back all target writes
                DB::connection($this->targetConnection)->rollBack();

                $run = new LegacyImportRun();
                $run->setConnection($this->targetConnection);
                $run->source_fingerprint = $fingerprint;
                $run->dry_run = true;
                $run->started_at = $startedAt;
                $run->completed_at = $completedAt;
                $run->counts = $allCounts;
                $run->anomalies = $allAnomalies;
                $run->status = $status;

                return $run;
            }

            // Persist the run record
            $run = LegacyImportRun::on($this->targetConnection)->create([
                'source_fingerprint' => $fingerprint,
                'dry_run' => false,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'counts' => $allCounts,
                'anomalies' => $allAnomalies,
                'status' => $status,
                'error_summary' => null,
            ]);

            return $run;

        } catch (\Throwable $e) {
            if ($dryRun) {
                DB::connection($this->targetConnection)->rollBack();
            }

            $run = new LegacyImportRun();
            $run->setConnection($this->targetConnection);
            $run->source_fingerprint = $fingerprint;
            $run->dry_run = $dryRun;
            $run->started_at = $startedAt;
            $run->completed_at = now();
            $run->counts = $allCounts;
            $run->anomalies = $allAnomalies;
            $run->status = 'failed';
            $run->error_summary = $e->getMessage() . "\n" . $e->getTraceAsString();

            if (!$dryRun && Schema::connection($this->targetConnection)->hasTable('legacy_import_runs')) {
                $run->save();
            }

            throw $e;
        }
    }

    /**
     * Run reconciliation verification only.
     *
     * @return array<string, mixed>
     */
    public function verify(): array
    {
        return $this->reporter->generateReport($this->legacyConnection, $this->targetConnection);
    }

    /**
     * Calculate a deterministic fingerprint of the legacy source database.
     */
    protected function calculateSourceFingerprint(): string
    {
        $tables = [
            'locations', 'manufacturers', 'devcats', 'files',
            'officials', 'developers', 'users', 'desktops',
            'laptops', 'servers', 'devices', 'storages',
            'consumables', 'entries', 'agreements', 'payments', 'tasks',
        ];

        $metadata = [];
        foreach ($tables as $table) {
            try {
                if (Schema::connection($this->legacyConnection)->hasTable($table)) {
                    $metadata[$table] = DB::connection($this->legacyConnection)->table($table)->count();
                }
            } catch (\Throwable) {
                // Table doesn't exist or cannot be accessed
            }
        }

        return hash('sha256', json_encode($metadata));
    }
}
