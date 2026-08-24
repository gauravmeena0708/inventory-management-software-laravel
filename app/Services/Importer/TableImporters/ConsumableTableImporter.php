<?php

namespace App\Services\Importer\TableImporters;

use App\Enums\StockEntryType;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Official;

class ConsumableTableImporter extends BaseTableImporter
{
    /**
     * Execute the consumable and stock entry import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importConsumables($progressCallback);
        $this->importEntries($progressCallback);
        $this->verifyStockBalances();

        return $this->counts;
    }

    /**
     * Import legacy consumables.
     */
    protected function importConsumables(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('consumables')) {
            return;
        }

        $query = $this->legacyQuery('consumables')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "Consumable #{$legacyId}";
                }

                $inStock = isset($rowArray['in_stock']) ? (int) $rowArray['in_stock'] : 0;
                $minQty = isset($rowArray['min_quantity']) ? (int) $rowArray['min_quantity'] : null;
                $maxQty = isset($rowArray['max_quantity']) ? (int) $rowArray['max_quantity'] : null;

                $attributes = [
                    'name' => $name,
                    'in_stock' => $inStock,
                    'min_quantity' => $minQty,
                    'max_quantity' => $maxQty,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Consumable::on($this->targetConnection)->updateOrCreate(
                    ['id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('consumables');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('consumables', $processed, $total);
            }
        });
    }

    /**
     * Import legacy stock entries.
     */
    protected function importEntries(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('entries')) {
            return;
        }

        $query = $this->legacyQuery('entries')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $consumableId = (int) ($rowArray['consumable_id'] ?? 0);

                // Verify consumable exists in target
                $consumableExists = Consumable::on($this->targetConnection)->where('id', $consumableId)->exists();
                if (!$consumableExists) {
                    $this->recordAnomaly(
                        'entries',
                        'missing_foreign_key',
                        "Stock entry #{$legacyId} references non-existent consumable ID {$consumableId}.",
                        ['entry_id' => $legacyId, 'consumable_id' => $consumableId]
                    );
                    continue;
                }

                // Map entry type
                $rawType = $rowArray['type'] ?? null;
                $type = $this->mapStockEntryType($rawType);

                // Validate recipient / issuer official
                $issuerId = $rowArray['issuer_id'] ?? null;
                if ($issuerId !== null) {
                    $officialExists = Official::on($this->targetConnection)->where('id', $issuerId)->exists();
                    if (!$officialExists) {
                        $this->recordAnomaly(
                            'entries',
                            'missing_foreign_key',
                            "Stock entry #{$legacyId} references non-existent official ID {$issuerId}.",
                            ['entry_id' => $legacyId, 'issuer_id' => $issuerId]
                        );
                        $issuerId = null;
                    }
                }

                $quantity = isset($rowArray['amount']) ? (int) $rowArray['amount'] : (isset($rowArray['quantity']) ? (int) $rowArray['quantity'] : 0);
                $stockAfter = isset($rowArray['stock']) ? (int) $rowArray['stock'] : (isset($rowArray['stock_after']) ? (int) $rowArray['stock_after'] : 0);

                $attributes = [
                    'consumable_id' => $consumableId,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_after' => $stockAfter,
                    'recipient_official_id' => $issuerId,
                    'remarks' => "Imported from legacy entry #{$legacyId}",
                    'idempotency_key' => "legacy-entry-{$legacyId}",
                    'created_at' => $rowArray['created_at'] ?? now(),
                ];

                Entry::on($this->targetConnection)->updateOrCreate(
                    ['legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('entries');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('entries', $processed, $total);
            }
        });
    }

    /**
     * Verify stock balance consistency between consumables table and entries ledger.
     */
    protected function verifyStockBalances(): void
    {
        $consumables = Consumable::on($this->targetConnection)->with('latestEntry')->get();

        foreach ($consumables as $consumable) {
            $latestEntry = $consumable->latestEntry;

            if ($latestEntry !== null && $consumable->in_stock !== $latestEntry->stock_after) {
                $this->recordAnomaly(
                    'consumables',
                    'stock_discrepancy',
                    "Consumable '{$consumable->name}' (ID {$consumable->id}) in_stock ({$consumable->in_stock}) differs from latest entry stock_after ({$latestEntry->stock_after}).",
                    [
                        'consumable_id' => $consumable->id,
                        'in_stock' => $consumable->in_stock,
                        'latest_entry_stock_after' => $latestEntry->stock_after,
                    ]
                );
            }
        }
    }

    /**
     * Map raw legacy type to StockEntryType enum.
     */
    protected function mapStockEntryType(mixed $rawType): StockEntryType
    {
        if ($rawType instanceof StockEntryType) {
            return $rawType;
        }

        $str = strtolower(trim((string) $rawType));

        return match ($str) {
            '1', 'purchase', 'in', 'inbound' => StockEntryType::PURCHASE,
            '2', 'issue', 'out', 'outbound' => StockEntryType::ISSUE,
            'adjustment_in' => StockEntryType::ADJUSTMENT_IN,
            'adjustment_out' => StockEntryType::ADJUSTMENT_OUT,
            default => StockEntryType::PURCHASE,
        };
    }
}
