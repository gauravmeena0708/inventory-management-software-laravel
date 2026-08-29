<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'asset_id',
        'official_id',
        'assigned_by',
        'assigned_at',
        'returned_at',
        'return_recorded_by',
        'source',
        'condition_out',
        'condition_in',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * Get the asset associated with the assignment.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * Get the official who received the assignment.
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'official_id');
    }

    /**
     * Get the user who assigned the asset.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who recorded the return of the asset.
     */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_recorded_by');
    }

    /**
     * Scope a query to only include active/open assignments.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Scope a query to only include completed/closed assignments.
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotNull('returned_at');
    }
}
