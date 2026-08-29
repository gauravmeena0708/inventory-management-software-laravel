<?php

namespace App\Models;

use App\Enums\ObservationSeverity;
use App\Enums\ObservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AuditObservation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'audit_engagement_id',
        'para_number',
        'title',
        'finding',
        'severity',
        'risk_category',
        'financial_implication',
        'currency',
        'status',
        'issued_at',
        'due_date',
        'created_by',
        'closed_by',
        'closed_at',
        'closure_reason',
    ];

    protected function casts(): array
    {
        return [
            'severity' => ObservationSeverity::class,
            'status' => ObservationStatus::class,
            'financial_implication' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(AuditEngagement::class, 'audit_engagement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'audit_observation_assets')
            ->withTimestamps();
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(AuditEvidence::class, 'audit_observation_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuditResponse::class, 'audit_observation_id')->orderBy('submitted_at', 'asc');
    }

    public function latestResponse(): BelongsTo
    {
        return $this->belongsTo(AuditResponse::class, 'latest_response_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['settled', 'dropped']);
    }

    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }
}
