<?php

namespace App\Models;

use App\Enums\AuditStatus;
use App\Enums\AuditType;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AuditEngagement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'audit_number',
        'audit_type',
        'audited_organizational_unit_id',
        'auditing_organizational_unit_id',
        'audit_from',
        'audit_to',
        'fieldwork_started_at',
        'fieldwork_completed_at',
        'status',
        'lead_auditor_user_id',
        'created_by',
        'scope_text',
        'report_date',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'audit_type' => AuditType::class,
            'status' => AuditStatus::class,
            'audit_from' => 'date',
            'audit_to' => 'date',
            'report_date' => 'date',
            'fieldwork_started_at' => 'datetime',
            'fieldwork_completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function auditedOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'audited_organizational_unit_id');
    }

    public function auditingOrganizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'auditing_organizational_unit_id');
    }

    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(AuditObservation::class, 'audit_engagement_id');
    }

    public function openObservations(): HasMany
    {
        return $this->hasMany(AuditObservation::class, 'audit_engagement_id')
            ->whereNotIn('status', ['settled', 'dropped']);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereHas('auditedOrganizationalUnit', fn ($sq) => app(OrganizationalVisibility::class)->apply($sq, $user))
                ->orWhereHas('auditingOrganizationalUnit', fn ($sq) => app(OrganizationalVisibility::class)->apply($sq, $user));
        });
    }
}
