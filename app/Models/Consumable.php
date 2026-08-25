<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Consumable extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sku',
        'unit',
        'in_stock',
        'min_quantity',
        'max_quantity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'in_stock' => 'integer',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
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
     * Get all stock ledger entries for this consumable.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'consumable_id');
    }

    /**
     * Get the latest stock ledger entry for this consumable.
     */
    public function latestEntry(): HasOne
    {
        return $this->hasOne(Entry::class, 'consumable_id')->latestOfMany('id');
    }

    /**
     * Check if the consumable is at or below its configured minimum stock threshold.
     */
    public function isLowStock(): bool
    {
        return $this->min_quantity !== null && $this->in_stock <= $this->min_quantity;
    }

    /**
     * Scope a query to only include consumables that have low stock.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereNotNull('min_quantity')
            ->whereColumn('in_stock', '<=', 'min_quantity');
    }

    /**
     * Scope a query to search consumables by name or SKU.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }
}
