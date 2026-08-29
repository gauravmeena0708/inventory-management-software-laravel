<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class StockTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'consumable_id',
        'source_location_id',
        'destination_location_id',
        'transaction_type',
        'quantity',
        'source_stock_after',
        'destination_stock_after',
        'recipient_official_id',
        'recorded_by',
        'accepted_by',
        'idempotency_key',
        'legacy_entry_id',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => StockTransactionType::class,
            'quantity' => 'integer',
            'source_stock_after' => 'integer',
            'destination_stock_after' => 'integer',
            'legacy_entry_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stock transactions are immutable.'));
        static::deleting(fn () => throw new LogicException('Stock transactions are immutable.'));
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'recipient_official_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function legacyEntry(): BelongsTo
    {
        return $this->belongsTo(Entry::class, 'legacy_entry_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visibilityQuery) use ($user): void {
            $visibilityQuery
                ->whereHas('sourceLocation', fn (Builder $locationQuery): Builder => $locationQuery->visibleTo($user))
                ->orWhereHas('destinationLocation', fn (Builder $locationQuery): Builder => $locationQuery->visibleTo($user));
        });
    }
}
