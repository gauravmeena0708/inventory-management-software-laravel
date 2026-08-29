<?php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetTransfer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transfer_number',
        'asset_id',
        'from_organizational_unit_id',
        'to_organizational_unit_id',
        'from_location_id',
        'to_location_id',
        'initiated_by',
        'approved_by',
        'status',
        'requested_at',
        'approved_at',
        'dispatched_at',
        'received_at',
        'received_by',
        'condition_at_dispatch',
        'condition_at_receipt',
        'reference_number',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $unitIds = app(OrganizationalVisibility::class)->readableUnitIds($user);

        if ($unitIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($unitIds) {
            $q->whereIn('from_organizational_unit_id', $unitIds->all())
                ->orWhereIn('to_organizational_unit_id', $unitIds->all());
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TransferStatus::PENDING_APPROVAL->value,
            TransferStatus::APPROVED->value,
            TransferStatus::IN_TRANSIT->value,
        ]);
    }
}
