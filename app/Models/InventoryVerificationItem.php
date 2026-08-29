<?php

namespace App\Models;

use App\Enums\VerificationResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryVerificationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_verification_id',
        'asset_id',
        'expected_organizational_unit_id',
        'observed_organizational_unit_id',
        'expected_location_id',
        'observed_location_id',
        'expected_official_id',
        'observed_official_id',
        'result',
        'verified_by',
        'verified_at',
        'remarks',
        'photo_attachment_id',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
    ];

    protected function casts(): array
    {
        return [
            'result' => VerificationResult::class,
            'verified_at' => 'datetime',
            'is_reconciled' => 'boolean',
            'reconciled_at' => 'datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(InventoryVerification::class, 'inventory_verification_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function expectedOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'expected_organizational_unit_id');
    }

    public function observedOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'observed_organizational_unit_id');
    }

    public function expectedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'expected_location_id');
    }

    public function observedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'observed_location_id');
    }

    public function expectedOfficial(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'expected_official_id');
    }

    public function observedOfficial(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'observed_official_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'photo_attachment_id');
    }
}
