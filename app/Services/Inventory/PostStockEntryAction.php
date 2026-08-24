<?php

namespace App\Services\Inventory;

use App\Enums\StockEntryType;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Official;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostStockEntryAction
{
    /**
     * Post an immutable stock ledger entry with pessimistic locking and idempotency protection.
     *
     * @throws InsufficientStockException
     * @throws InvalidArgumentException
     */
    public function execute(
        Consumable $consumable,
        StockEntryType $type,
        int $quantity,
        ?Official $recipient,
        User $user,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Entry {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Stock entry quantity must be greater than zero. [Given: {$quantity}]");
        }

        return DB::transaction(function () use (
            $consumable,
            $type,
            $quantity,
            $recipient,
            $user,
            $remarks,
            $idempotencyKey
        ) {
            // Idempotency check: if an entry with this key exists, return it immediately without re-mutating stock
            if (!empty($idempotencyKey)) {
                $existingEntry = Entry::where('idempotency_key', $idempotencyKey)->first();
                if ($existingEntry) {
                    return $existingEntry;
                }
            }

            /** @var Consumable $lockedConsumable */
            $lockedConsumable = Consumable::where('id', $consumable->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStock = (int) $lockedConsumable->in_stock;

            if ($type->isOutbound()) {
                if ($currentStock < $quantity) {
                    throw new InsufficientStockException(
                        consumable: $lockedConsumable,
                        requested: $quantity,
                        available: $currentStock
                    );
                }
                $newStock = $currentStock - $quantity;
            } else {
                $newStock = $currentStock + $quantity;
            }

            // Update consumable in_stock balance
            $lockedConsumable->update(['in_stock' => $newStock]);

            // Create immutable stock ledger entry
            /** @var Entry $entry */
            $entry = Entry::create([
                'consumable_id' => $lockedConsumable->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_after' => $newStock,
                'recipient_official_id' => $recipient?->id,
                'recorded_by' => $user->id,
                'remarks' => $remarks,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $entry;
        });
    }
}
