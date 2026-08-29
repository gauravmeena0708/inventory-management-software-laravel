<?php

namespace App\Services\Stock;

use App\Enums\LocationType;
use App\Enums\StockTransactionType;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Location;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LegacyLocationStockMigrator
{
    /**
     * Copy the immutable legacy ledger and establish opening balances at a designated store.
     * Existing entries are never changed or deleted, and rerunning this method is safe.
     *
     * @return array{balances_created:int, entries_copied:int, discrepancies:array<int, array<string, int|string|null>>, conflicts:array<int, array<string, int|string>>}
     */
    public function migrateTo(Location $store): array
    {
        $this->assertDesignatedStore($store);

        return DB::transaction(function () use ($store): array {
            $result = [
                'balances_created' => 0,
                'entries_copied' => 0,
                'discrepancies' => [],
                'conflicts' => [],
            ];

            Consumable::query()->orderBy('id')->lockForUpdate()->each(function (Consumable $consumable) use ($store, &$result): void {
                $balance = StockBalance::where('consumable_id', $consumable->id)
                    ->where('location_id', $store->id)
                    ->first();

                if (! $balance) {
                    $otherBalanceExists = StockBalance::where('consumable_id', $consumable->id)->exists();
                    if ($otherBalanceExists) {
                        $result['conflicts'][] = [
                            'consumable_id' => $consumable->id,
                            'reason' => 'Location balances already exist outside the designated legacy store.',
                        ];
                    } else {
                        StockBalance::create([
                            'consumable_id' => $consumable->id,
                            'location_id' => $store->id,
                            'quantity' => $consumable->in_stock,
                            'min_quantity' => $consumable->min_quantity,
                            'max_quantity' => $consumable->max_quantity,
                        ]);
                        $result['balances_created']++;
                    }
                }

                $entries = Entry::where('consumable_id', $consumable->id)->oldest('id')->get();
                foreach ($entries as $entry) {
                    if (StockTransaction::where('legacy_entry_id', $entry->id)->exists()) {
                        continue;
                    }

                    $type = StockTransactionType::from($entry->type->value);
                    StockTransaction::create([
                        'consumable_id' => $consumable->id,
                        'source_location_id' => $type->isOutbound() ? $store->id : null,
                        'destination_location_id' => $type->isInbound() ? $store->id : null,
                        'transaction_type' => $type,
                        'quantity' => $entry->quantity,
                        'source_stock_after' => $type->isOutbound() ? $entry->stock_after : null,
                        'destination_stock_after' => $type->isInbound() ? $entry->stock_after : null,
                        'recipient_official_id' => $entry->recipient_official_id,
                        'recorded_by' => $entry->recorded_by,
                        'idempotency_key' => 'legacy-entry:'.$entry->id,
                        'legacy_entry_id' => $entry->id,
                        'remarks' => $entry->remarks,
                        'created_at' => $entry->created_at,
                    ]);
                    $result['entries_copied']++;
                }

                $latest = $entries->last();
                if ($latest && (int) $latest->stock_after !== (int) $consumable->in_stock) {
                    $result['discrepancies'][] = [
                        'consumable_id' => $consumable->id,
                        'catalog_stock' => (int) $consumable->in_stock,
                        'ledger_stock_after' => (int) $latest->stock_after,
                        'difference' => (int) $consumable->in_stock - (int) $latest->stock_after,
                    ];
                }
            });

            return $result;
        }, 5);
    }

    private function assertDesignatedStore(Location $store): void
    {
        $store->loadMissing('site.organizationalUnits');
        if (
            $store->location_type !== LocationType::STORE
            || ! $store->is_active
            || $store->trashed()
            || ! $store->site
            || ! $store->site->is_active
            || $store->site->organizationalUnits->where('is_active', true)->isEmpty()
        ) {
            throw new InvalidArgumentException('Legacy stock must be migrated to an active STORE mapped to an active organizational unit.');
        }
    }
}
