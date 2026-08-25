<?php

namespace App\Services\Stock;

use App\Enums\StockTransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Official;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LocationStockService
{
    public function __construct(
        private readonly OrganizationalContext $organizationalContext
    ) {}

    public function purchase(
        Consumable $consumable,
        Location $destination,
        int $quantity,
        User $recordedBy,
        string $idempotencyKey,
        ?string $remarks = null
    ): StockTransaction {
        $this->assertPositiveQuantity($quantity);
        $this->authorizeLocationMutation($recordedBy, $destination, 'purchase stock at');

        return DB::transaction(function () use ($consumable, $destination, $quantity, $recordedBy, $idempotencyKey, $remarks): StockTransaction {
            if ($existing = $this->matchingReplay(
                $idempotencyKey,
                StockTransactionType::PURCHASE,
                $consumable,
                null,
                $destination,
                $quantity,
                $recordedBy
            )) {
                return $existing;
            }

            $balance = $this->lockedBalance($consumable, $destination);
            $balance->quantity += $quantity;
            $balance->save();

            $transaction = StockTransaction::create([
                'consumable_id' => $consumable->id,
                'destination_location_id' => $destination->id,
                'transaction_type' => StockTransactionType::PURCHASE,
                'quantity' => $quantity,
                'destination_stock_after' => $balance->quantity,
                'recorded_by' => $recordedBy->id,
                'idempotency_key' => $this->requiredIdempotencyKey($idempotencyKey),
                'remarks' => $remarks,
            ]);

            $this->synchronizeLegacyAggregate($consumable);

            return $transaction;
        }, 5);
    }

    public function issue(
        Consumable $consumable,
        Location $source,
        int $quantity,
        User $recordedBy,
        string $idempotencyKey,
        ?Official $recipient = null,
        ?string $remarks = null
    ): StockTransaction {
        $this->assertPositiveQuantity($quantity);
        $this->authorizeLocationMutation($recordedBy, $source, 'issue stock from');

        return DB::transaction(function () use ($consumable, $source, $quantity, $recordedBy, $idempotencyKey, $recipient, $remarks): StockTransaction {
            if ($existing = $this->matchingReplay(
                $idempotencyKey,
                StockTransactionType::ISSUE,
                $consumable,
                $source,
                null,
                $quantity,
                $recordedBy,
                null,
                $recipient
            )) {
                return $existing;
            }

            $balance = $this->lockedBalance($consumable, $source);
            $this->assertSufficientStock($consumable, $balance, $quantity);
            $balance->quantity -= $quantity;
            $balance->save();

            $transaction = StockTransaction::create([
                'consumable_id' => $consumable->id,
                'source_location_id' => $source->id,
                'transaction_type' => StockTransactionType::ISSUE,
                'quantity' => $quantity,
                'source_stock_after' => $balance->quantity,
                'recipient_official_id' => $recipient?->id,
                'recorded_by' => $recordedBy->id,
                'idempotency_key' => $this->requiredIdempotencyKey($idempotencyKey),
                'remarks' => $remarks,
            ]);

            $this->synchronizeLegacyAggregate($consumable);

            return $transaction;
        }, 5);
    }

    public function adjust(
        Consumable $consumable,
        Location $location,
        int $quantityDelta,
        User $recordedBy,
        string $idempotencyKey,
        ?string $remarks = null
    ): StockTransaction {
        if ($quantityDelta === 0) {
            throw new InvalidArgumentException('Stock adjustment must not be zero.');
        }

        $this->authorizeLocationMutation($recordedBy, $location, 'adjust stock at');
        $type = $quantityDelta > 0
            ? StockTransactionType::ADJUSTMENT_IN
            : StockTransactionType::ADJUSTMENT_OUT;
        $quantity = abs($quantityDelta);

        return DB::transaction(function () use ($consumable, $location, $quantityDelta, $quantity, $recordedBy, $idempotencyKey, $remarks, $type): StockTransaction {
            $source = $quantityDelta < 0 ? $location : null;
            $destination = $quantityDelta > 0 ? $location : null;

            if ($existing = $this->matchingReplay(
                $idempotencyKey,
                $type,
                $consumable,
                $source,
                $destination,
                $quantity,
                $recordedBy
            )) {
                return $existing;
            }

            $balance = $this->lockedBalance($consumable, $location);
            if ($quantityDelta < 0) {
                $this->assertSufficientStock($consumable, $balance, $quantity);
            }

            $balance->quantity += $quantityDelta;
            $balance->save();

            $transaction = StockTransaction::create([
                'consumable_id' => $consumable->id,
                'source_location_id' => $source?->id,
                'destination_location_id' => $destination?->id,
                'transaction_type' => $type,
                'quantity' => $quantity,
                'source_stock_after' => $source ? $balance->quantity : null,
                'destination_stock_after' => $destination ? $balance->quantity : null,
                'recorded_by' => $recordedBy->id,
                'idempotency_key' => $this->requiredIdempotencyKey($idempotencyKey),
                'remarks' => $remarks,
            ]);

            $this->synchronizeLegacyAggregate($consumable);

            return $transaction;
        }, 5);
    }

    public function transfer(
        Consumable $consumable,
        Location $source,
        Location $destination,
        int $quantity,
        User $recordedBy,
        User $acceptedBy,
        string $idempotencyKey,
        ?string $remarks = null
    ): StockTransaction {
        $this->assertPositiveQuantity($quantity);
        if ($source->is($destination)) {
            throw new InvalidArgumentException('Source and destination stock locations must be different.');
        }

        $this->authorizeLocationMutation($recordedBy, $source, 'transfer stock from');
        $this->authorizeLocationMutation($acceptedBy, $destination, 'accept stock at');

        return DB::transaction(function () use ($consumable, $source, $destination, $quantity, $recordedBy, $acceptedBy, $idempotencyKey, $remarks): StockTransaction {
            if ($existing = $this->matchingReplay(
                $idempotencyKey,
                StockTransactionType::TRANSFER,
                $consumable,
                $source,
                $destination,
                $quantity,
                $recordedBy,
                $acceptedBy
            )) {
                return $existing;
            }

            $sourceBalance = $this->ensureBalance($consumable, $source);
            $destinationBalance = $this->ensureBalance($consumable, $destination);

            $lockedBalances = StockBalance::query()
                ->whereKey([$sourceBalance->id, $destinationBalance->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('location_id');

            $sourceBalance = $lockedBalances->get($source->id);
            $destinationBalance = $lockedBalances->get($destination->id);

            $this->assertSufficientStock($consumable, $sourceBalance, $quantity);
            $sourceBalance->quantity -= $quantity;
            $destinationBalance->quantity += $quantity;
            $sourceBalance->save();
            $destinationBalance->save();

            $transaction = StockTransaction::create([
                'consumable_id' => $consumable->id,
                'source_location_id' => $source->id,
                'destination_location_id' => $destination->id,
                'transaction_type' => StockTransactionType::TRANSFER,
                'quantity' => $quantity,
                'source_stock_after' => $sourceBalance->quantity,
                'destination_stock_after' => $destinationBalance->quantity,
                'recorded_by' => $recordedBy->id,
                'accepted_by' => $acceptedBy->id,
                'idempotency_key' => $this->requiredIdempotencyKey($idempotencyKey),
                'remarks' => $remarks,
            ]);

            $this->synchronizeLegacyAggregate($consumable);

            return $transaction;
        }, 5);
    }

    private function ensureBalance(Consumable $consumable, Location $location): StockBalance
    {
        return StockBalance::firstOrCreate(
            ['consumable_id' => $consumable->id, 'location_id' => $location->id],
            [
                'quantity' => 0,
                'min_quantity' => $consumable->min_quantity,
                'max_quantity' => $consumable->max_quantity,
            ]
        );
    }

    private function lockedBalance(Consumable $consumable, Location $location): StockBalance
    {
        $balance = $this->ensureBalance($consumable, $location);

        return StockBalance::query()->whereKey($balance->id)->lockForUpdate()->firstOrFail();
    }

    private function synchronizeLegacyAggregate(Consumable $consumable): void
    {
        $total = (int) StockBalance::where('consumable_id', $consumable->id)->sum('quantity');
        Consumable::whereKey($consumable->id)->update(['in_stock' => $total]);
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Stock transaction quantity must be greater than zero.');
        }
    }

    private function assertSufficientStock(Consumable $consumable, StockBalance $balance, int $quantity): void
    {
        if ($balance->quantity < $quantity) {
            throw new InsufficientStockException($consumable, $quantity, $balance->quantity);
        }
    }

    private function authorizeLocationMutation(User $user, Location $location, string $operation): void
    {
        if (! $user->canPostStockEntries()) {
            throw new AuthorizationException("You are not permitted to {$operation} this location.");
        }

        $location->loadMissing('site.organizationalUnits');
        if (
            ! $location->is_active
            || $location->trashed()
            || ! $location->site
            || ! $location->site->is_active
            || $location->site->trashed()
        ) {
            throw new AuthorizationException('Stock may only be posted at an active mapped location.');
        }

        $writable = $location->site->organizationalUnits
            ->where('is_active', true)
            ->contains(fn ($unit): bool => $this->organizationalContext->canWrite($user, $unit->id));

        if (! $writable) {
            throw new AuthorizationException("You do not have organizational permission to {$operation} this location.");
        }
    }

    private function requiredIdempotencyKey(string $idempotencyKey): string
    {
        $key = trim($idempotencyKey);
        if ($key === '' || mb_strlen($key) > 191) {
            throw new InvalidArgumentException('A non-empty idempotency key of at most 191 characters is required.');
        }

        return $key;
    }

    private function matchingReplay(
        string $idempotencyKey,
        StockTransactionType $type,
        Consumable $consumable,
        ?Location $source,
        ?Location $destination,
        int $quantity,
        User $recordedBy,
        ?User $acceptedBy = null,
        ?Official $recipient = null
    ): ?StockTransaction {
        $key = $this->requiredIdempotencyKey($idempotencyKey);
        $existing = StockTransaction::where('idempotency_key', $key)->first();
        if (! $existing) {
            return null;
        }

        $expected = [
            'transaction_type' => $type->value,
            'consumable_id' => $consumable->id,
            'source_location_id' => $source?->id,
            'destination_location_id' => $destination?->id,
            'quantity' => $quantity,
            'recorded_by' => $recordedBy->id,
            'accepted_by' => $acceptedBy?->id,
            'recipient_official_id' => $recipient?->id,
        ];

        foreach ($expected as $attribute => $value) {
            $actual = $existing->getRawOriginal($attribute);
            if ($actual !== null) {
                $actual = $attribute === 'transaction_type' ? (string) $actual : (int) $actual;
            }
            if ($actual !== $value) {
                throw new InvalidArgumentException('The idempotency key was already used for a different stock transaction.');
            }
        }

        return $existing;
    }
}
