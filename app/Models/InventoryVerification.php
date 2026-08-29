<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class InventoryVerification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'organizational_unit_id',
        'site_id',
        'name',
        'financial_year',
        'verification_type',
        'planned_from',
        'planned_to',
        'started_at',
        'completed_at',
        'certified_at',
        'status',
        'created_by',
        'approved_by',
        'remarks',
        'snapshot_population',
    ];

    protected function casts(): array
    {
        return [
            'verification_type' => VerificationType::class,
            'status' => VerificationStatus::class,
            'planned_from' => 'date',
            'planned_to' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'certified_at' => 'datetime',
            'snapshot_population' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryVerificationItem::class, 'inventory_verification_id');
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(InventoryVerificationItem::class, 'inventory_verification_id')
            ->where('result', '!=', 'verified');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }
}
