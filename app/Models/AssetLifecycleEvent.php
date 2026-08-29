<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLifecycleEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'event_type',
        'occurred_at',
        'actor_user_id',
        'from_status',
        'to_status',
        'from_organizational_unit_id',
        'to_organizational_unit_id',
        'from_location_id',
        'to_location_id',
        'reference_type',
        'reference_id',
        'reference_number',
        'remarks',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => LifecycleEventType::class,
            'occurred_at' => 'datetime',
            'from_status' => AssetStatus::class,
            'to_status' => AssetStatus::class,
            'metadata' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function fromOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'from_organizational_unit_id');
    }

    public function toOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'to_organizational_unit_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function scopeForAsset(Builder $query, int $assetId): Builder
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('occurred_at', 'asc')->orderBy('id', 'asc');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('occurred_at', 'desc')->orderBy('id', 'desc');
    }
}
