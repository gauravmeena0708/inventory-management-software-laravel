<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPlacement extends Model
{
    protected $fillable = [
        'asset_id',
        'location_id',
        'position_x',
        'position_y',
        'position_z',
        'rack_start_unit',
        'rack_unit_height',
        'placed_at',
        'removed_at',
        'open_marker',
        'placed_by',
        'source',
        'remarks',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'removed_at' => 'datetime',
        'open_marker' => 'boolean',
        'position_x' => 'decimal:2',
        'position_y' => 'decimal:2',
        'position_z' => 'decimal:2',
        'rack_start_unit' => 'integer',
        'rack_unit_height' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('open_marker', true)
            ->whereNull('removed_at');
    }
}
