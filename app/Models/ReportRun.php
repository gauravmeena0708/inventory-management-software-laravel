<?php

namespace App\Models;

use App\Enums\ReportRunStatus;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ReportRun extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'report_definition_id',
        'organizational_unit_id',
        'scope_type',
        'reporting_from',
        'reporting_to',
        'as_on_date',
        'data_cutoff_at',
        'parameters',
        'status',
        'generated_by',
        'generated_at',
        'prepared_by',
        'prepared_at',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'version',
        'snapshot_data',
        'rendered_file_attachment_id',
        'sha256',
        'supersedes_report_run_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportRunStatus::class,
            'reporting_from' => 'date',
            'reporting_to' => 'date',
            'as_on_date' => 'date',
            'data_cutoff_at' => 'datetime',
            'generated_at' => 'datetime',
            'prepared_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'parameters' => 'array',
            'snapshot_data' => 'array',
            'version' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_definition_id');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function generatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function renderedFile(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'rendered_file_attachment_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(ReportRun::class, 'supersedes_report_run_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'supersedes_report_run_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }

    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('status', ReportRunStatus::FINAL->value);
    }
}
