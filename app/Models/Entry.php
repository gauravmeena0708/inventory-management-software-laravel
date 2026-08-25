<?php

namespace App\Models;

use App\Enums\StockEntryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Entry extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Disable updated_at to enforce ledger immutability.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consumable_id',
        'type',
        'quantity',
        'stock_after',
        'recipient_official_id',
        'recorded_by',
        'remarks',
        'idempotency_key',
        'legacy_id',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockEntryType::class,
            'quantity' => 'integer',
            'stock_after' => 'integer',
            'legacy_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    /**
     * Get the consumable associated with this ledger entry.
     */
    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class, 'consumable_id');
    }

    /**
     * Get the official who received the consumable (for outbound issues).
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'recipient_official_id');
    }

    /**
     * Backward-compatibility alias for legacy code referencing issuer.
     */
    public function issuer(): BelongsTo
    {
        return $this->recipient();
    }

    /**
     * Get the user who recorded this stock entry.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
