<?php

namespace App\Models;

use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumable_id',
        'location_id',
        'quantity',
        'min_quantity',
        'max_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
        ];
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'location',
            fn (Builder $locationQuery): Builder => app(OrganizationalVisibility::class)
                ->applyToLocations($locationQuery, $user)
        );
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query
            ->whereNotNull($query->qualifyColumn('min_quantity'))
            ->whereColumn(
                $query->qualifyColumn('quantity'),
                '<=',
                $query->qualifyColumn('min_quantity')
            );
    }

    public function isLowStock(): bool
    {
        return $this->min_quantity !== null && $this->quantity <= $this->min_quantity;
    }
}
