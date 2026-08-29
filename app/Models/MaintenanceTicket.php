<?php

namespace App\Models;

use App\Enums\MaintenanceSeverity;
use App\Enums\MaintenanceStatus;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MaintenanceTicket extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'asset_id',
        'organizational_unit_id',
        'ticket_number',
        'issue_category',
        'issue_description',
        'reported_by_user_id',
        'reported_by_official_id',
        'reported_at',
        'status',
        'severity',
        'agreement_id',
        'vendor_id',
        'vendor_name',
        'external_reference',
        'sent_for_repair_at',
        'repair_started_at',
        'completed_at',
        'returned_at',
        'diagnosis',
        'resolution',
        'cost',
        'currency',
        'covered_under_warranty',
        'covered_under_amc',
        'downtime_minutes',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => MaintenanceStatus::class,
            'severity' => MaintenanceSeverity::class,
            'reported_at' => 'datetime',
            'sent_for_repair_at' => 'datetime',
            'repair_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'returned_at' => 'datetime',
            'cost' => 'decimal:2',
            'covered_under_warranty' => 'boolean',
            'covered_under_amc' => 'boolean',
            'downtime_minutes' => 'integer',
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

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function reportedByOfficial(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'reported_by_official_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'vendor_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            MaintenanceStatus::CLOSED->value,
            MaintenanceStatus::CANCELLED->value,
        ]);
    }
}
