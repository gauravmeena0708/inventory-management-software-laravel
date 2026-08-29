<?php

namespace App\Services\Importer\TableImporters;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BaseTableImporter
{
    /**
     * The legacy database connection name.
     */
    protected string $legacyConnection;

    /**
     * The target database connection name (null uses default).
     */
    protected ?string $targetConnection;

    /**
     * Whether this import is running in dry-run mode.
     */
    protected bool $dryRun;

    /**
     * Chunk size for processing large datasets.
     */
    protected int $chunkSize;

    /**
     * Array of recorded anomalies during import.
     */
    protected array $anomalies = [];

    /**
     * Map of entity/table names to imported counts.
     */
    protected array $counts = [];

    /**
     * Create a new table importer instance.
     */
    public function __construct(
        string $legacyConnection = 'legacy',
        ?string $targetConnection = null,
        bool $dryRun = false,
        int $chunkSize = 500
    ) {
        $this->legacyConnection = $legacyConnection;
        $this->targetConnection = $targetConnection;
        $this->dryRun = $dryRun;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the table import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    abstract public function import(?callable $progressCallback = null): array;

    /**
     * Check if a table exists in the legacy database.
     */
    protected function hasLegacyTable(string $table): bool
    {
        try {
            return Schema::connection($this->legacyConnection)->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get a query builder for the legacy table.
     */
    protected function legacyQuery(string $table): Builder
    {
        return DB::connection($this->legacyConnection)->table($table);
    }

    /**
     * Get a query builder for the target table.
     */
    protected function targetQuery(string $table): Builder
    {
        return DB::connection($this->targetConnection)->table($table);
    }

    /**
     * Record an anomaly encountered during migration.
     */
    protected function recordAnomaly(string $table, string $type, string $message, array $context = []): void
    {
        $this->anomalies[] = [
            'table' => $table,
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'recorded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Increment the count of successfully processed records for an entity.
     */
    protected function incrementCount(string $entity, int $amount = 1): void
    {
        if (!isset($this->counts[$entity])) {
            $this->counts[$entity] = 0;
        }

        $this->counts[$entity] += $amount;
    }

    /**
     * Get all recorded anomalies.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAnomalies(): array
    {
        return $this->anomalies;
    }

    /**
     * Get all entity counts.
     *
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts;
    }
}
