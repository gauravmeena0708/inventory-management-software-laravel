<?php

namespace App\Services\Importer;

use App\Models\Agreement;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconciliationReporter
{
    /**
     * Generate a comprehensive reconciliation report comparing legacy and target databases.
     *
     * @param string $legacyConnection
     * @param string|null $targetConnection
     * @return array<string, mixed>
     */
    public function generateReport(string $legacyConnection = 'legacy', ?string $targetConnection = null): array
    {
        $counts = $this->calculateCounts($legacyConnection, $targetConnection);
        $financialParity = $this->calculateFinancialParity($legacyConnection, $targetConnection);
        $stockDiscrepancies = $this->checkStockDiscrepancies($targetConnection);
        $anomalies = $this->checkOrphanedReferences($targetConnection);
        $unmapped = $this->checkUnmappedRecords($legacyConnection, $targetConnection);

        $hasCountMismatch = false;
        foreach ($counts['entities'] as $entity => $data) {
            if ($data['legacy'] !== $data['target']) {
                $hasCountMismatch = true;
                break;
            }
        }

        $isClean = !$hasCountMismatch
            && $financialParity['is_parity']
            && empty($stockDiscrepancies)
            && empty($unmapped);

        return [
            'counts' => $counts,
            'financial_parity' => $financialParity,
            'stock_discrepancies' => $stockDiscrepancies,
            'anomalies' => $anomalies,
            'unmapped' => $unmapped,
            'is_clean' => $isClean,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate row counts per entity for legacy and target databases.
     */
    protected function calculateCounts(string $legacyConnection, ?string $targetConnection): array
    {
        $entities = [
            'locations' => [
                'legacy' => $this->safeCount($legacyConnection, 'locations'),
                'target' => $this->safeCount($targetConnection, 'locations'),
            ],
            'manufacturers' => [
                'legacy' => $this->safeCount($legacyConnection, 'manufacturers'),
                'target' => $this->safeCount($targetConnection, 'manufacturers'),
            ],
            'devcats' => [
                'legacy' => $this->safeCount($legacyConnection, 'devcats'),
                'target' => $this->safeCount($targetConnection, 'devcats'),
            ],
            'files' => [
                'legacy' => $this->safeCount($legacyConnection, 'files'),
                'target' => $this->safeCount($targetConnection, 'files'),
            ],
            'officials' => [
                'legacy' => $this->safeCount($legacyConnection, 'officials'),
                'target' => $this->safeCount($targetConnection, 'officials'),
            ],
            'developers' => [
                'legacy' => $this->safeCount($legacyConnection, 'developers'),
                'target' => $this->safeCount($targetConnection, 'developers'),
            ],
            'users' => [
                'legacy' => $this->safeCount($legacyConnection, 'users'),
                'target' => $this->safeCount($targetConnection, 'users'),
            ],
            'desktops' => [
                'legacy' => $this->safeCount($legacyConnection, 'desktops'),
                'target' => Asset::on($targetConnection)->where('legacy_source', 'desktop')->count(),
            ],
            'laptops' => [
                'legacy' => $this->safeCount($legacyConnection, 'laptops'),
                'target' => Asset::on($targetConnection)->where('legacy_source', 'laptop')->count(),
            ],
            'servers' => [
                'legacy' => $this->safeCount($legacyConnection, 'servers'),
                'target' => Asset::on($targetConnection)->where('legacy_source', 'server')->count(),
            ],
            'devices' => [
                'legacy' => $this->safeCount($legacyConnection, 'devices'),
                'target' => Asset::on($targetConnection)->where('legacy_source', 'device')->count(),
            ],
            'storages' => [
                'legacy' => $this->safeCount($legacyConnection, 'storages'),
                'target' => Asset::on($targetConnection)->where('legacy_source', 'storage')->count(),
            ],
            'consumables' => [
                'legacy' => $this->safeCount($legacyConnection, 'consumables'),
                'target' => $this->safeCount($targetConnection, 'consumables'),
            ],
            'entries' => [
                'legacy' => $this->safeCount($legacyConnection, 'entries'),
                'target' => $this->safeCount($targetConnection, 'entries'),
            ],
            'agreements' => [
                'legacy' => $this->safeCount($legacyConnection, 'agreements'),
                'target' => $this->safeCount($targetConnection, 'agreements'),
            ],
            'payments' => [
                'legacy' => $this->safeCount($legacyConnection, 'payments'),
                'target' => $this->safeCount($targetConnection, 'payments'),
            ],
            'tasks' => [
                'legacy' => $this->safeCount($legacyConnection, 'tasks'),
                'target' => $this->safeCount($targetConnection, 'tasks'),
            ],
        ];

        // Total unified assets calculation
        $legacyTotalAssets = $entities['desktops']['legacy']
            + $entities['laptops']['legacy']
            + $entities['servers']['legacy']
            + $entities['devices']['legacy']
            + $entities['storages']['legacy'];

        $targetTotalAssets = Asset::on($targetConnection)->count();

        $entities['total_assets'] = [
            'legacy' => $legacyTotalAssets,
            'target' => $targetTotalAssets,
        ];

        // Add difference calculation
        foreach ($entities as $name => &$counts) {
            $counts['difference'] = $counts['target'] - $counts['legacy'];
            $counts['match'] = ($counts['difference'] === 0);
        }

        return [
            'entities' => $entities,
            'legacy_total_assets' => $legacyTotalAssets,
            'target_total_assets' => $targetTotalAssets,
        ];
    }

    /**
     * Calculate financial totals parity for agreements and payments.
     */
    protected function calculateFinancialParity(string $legacyConnection, ?string $targetConnection): array
    {
        $legacyAgreementsTotal = 0.0;
        if ($this->hasTable($legacyConnection, 'agreements')) {
            $legacyAgreementsTotal = (float) (DB::connection($legacyConnection)->table('agreements')->sum('annual_cost') ?? 0);
        }

        $targetAgreementsTotal = (float) (Agreement::on($targetConnection)->sum('annual_cost') ?? 0);
        $agreementsDiff = round($targetAgreementsTotal - $legacyAgreementsTotal, 2);

        $legacyPaymentsTotal = 0.0;
        if ($this->hasTable($legacyConnection, 'payments')) {
            $legacyPaymentsTotal = (float) (DB::connection($legacyConnection)->table('payments')->sum('amount') ?? 0);
        }

        $targetPaymentsTotal = (float) (Payment::on($targetConnection)->sum('amount') ?? 0);
        $paymentsDiff = round($targetPaymentsTotal - $legacyPaymentsTotal, 2);

        $isParity = (abs($agreementsDiff) < 0.01) && (abs($paymentsDiff) < 0.01);

        return [
            'legacy_agreements_total' => $legacyAgreementsTotal,
            'target_agreements_total' => $targetAgreementsTotal,
            'agreements_diff' => $agreementsDiff,
            'legacy_payments_total' => $legacyPaymentsTotal,
            'target_payments_total' => $targetPaymentsTotal,
            'payments_diff' => $paymentsDiff,
            'is_parity' => $isParity,
        ];
    }

    /**
     * Check for stock balance discrepancies between consumables and entries.
     */
    protected function checkStockDiscrepancies(?string $targetConnection): array
    {
        $discrepancies = [];

        if (!$this->hasTable($targetConnection, 'consumables') || !$this->hasTable($targetConnection, 'entries')) {
            return $discrepancies;
        }

        $consumables = Consumable::on($targetConnection)->with('latestEntry')->get();

        foreach ($consumables as $consumable) {
            $latest = $consumable->latestEntry;
            if ($latest !== null && $consumable->in_stock !== $latest->stock_after) {
                $discrepancies[] = [
                    'consumable_id' => $consumable->id,
                    'name' => $consumable->name,
                    'in_stock' => $consumable->in_stock,
                    'latest_entry_stock' => $latest->stock_after,
                    'difference' => $consumable->in_stock - $latest->stock_after,
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Check for unmapped legacy records in target database.
     */
    protected function checkUnmappedRecords(string $legacyConnection, ?string $targetConnection): array
    {
        $unmapped = [];

        $assetTables = [
            'desktop' => 'desktops',
            'laptop' => 'laptops',
            'server' => 'servers',
            'device' => 'devices',
            'storage' => 'storages',
        ];

        foreach ($assetTables as $source => $table) {
            if ($this->hasTable($legacyConnection, $table)) {
                $legacyIds = DB::connection($legacyConnection)->table($table)->pluck('id')->toArray();
                $targetLegacyIds = Asset::on($targetConnection)
                    ->where('legacy_source', $source)
                    ->pluck('legacy_id')
                    ->toArray();

                $missing = array_diff($legacyIds, $targetLegacyIds);
                if (!empty($missing)) {
                    $unmapped[$table] = array_values($missing);
                }
            }
        }

        return $unmapped;
    }

    /**
     * Check for orphaned references or missing foreign keys in target database.
     */
    protected function checkOrphanedReferences(?string $targetConnection): array
    {
        $orphaned = [];

        // Check assets with location_id that doesn't exist
        if ($this->hasTable($targetConnection, 'assets') && $this->hasTable($targetConnection, 'locations')) {
            $count = DB::connection($targetConnection)->table('assets')
                ->whereNotNull('location_id')
                ->whereNotIn('location_id', function ($query) use ($targetConnection) {
                    $query->select('id')->from('locations');
                })
                ->count();

            if ($count > 0) {
                $orphaned[] = ['table' => 'assets', 'column' => 'location_id', 'orphaned_count' => $count];
            }
        }

        // Check assets with assigned_official_id that doesn't exist
        if ($this->hasTable($targetConnection, 'assets') && $this->hasTable($targetConnection, 'officials')) {
            $count = DB::connection($targetConnection)->table('assets')
                ->whereNotNull('assigned_official_id')
                ->whereNotIn('assigned_official_id', function ($query) use ($targetConnection) {
                    $query->select('id')->from('officials');
                })
                ->count();

            if ($count > 0) {
                $orphaned[] = ['table' => 'assets', 'column' => 'assigned_official_id', 'orphaned_count' => $count];
            }
        }

        return $orphaned;
    }

    /**
     * Safely count rows in a database table.
     */
    protected function safeCount(?string $connection, string $table): int
    {
        try {
            if (!$this->hasTable($connection, $table)) {
                return 0;
            }

            return DB::connection($connection)->table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Check if a table exists on the given connection.
     */
    protected function hasTable(?string $connection, string $table): bool
    {
        try {
            return Schema::connection($connection)->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
