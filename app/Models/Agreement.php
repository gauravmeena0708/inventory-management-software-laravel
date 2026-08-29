<?php

namespace App\Models;

use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Agreement extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'agency',
        'file_id',
        'type',
        'expiry',
        'annual_cost',
        'currency',
        'billing_interval_months',
        'billing_anchor_date',
        'paid_till',
        'remarks',
        'legacy_payload',
        'organizational_unit_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry' => 'date',
            'billing_anchor_date' => 'date',
            'paid_till' => 'date',
            'legacy_payload' => 'array',
            'annual_cost' => 'decimal:2',
            'billing_interval_months' => 'integer',
        ];
    }

    /**
     * Get the options for activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    /**
     * Get all payments scheduled or completed for this agreement.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'agreement_id');
    }

    /**
     * Get the file registry record associated with this agreement.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class, 'file_id');
    }

    /**
     * Get all attachments associated with this agreement.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * Get assets covered under this agreement.
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'agreement_asset')
            ->withPivot(['id', 'coverage_type', 'coverage_start', 'coverage_end', 'sla_reference', 'remarks'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }

    /**
     * Scope a query to only include agreements expiring within the given number of days.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 180): Builder
    {
        return $query->whereNotNull('expiry')
            ->whereBetween('expiry', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    /**
     * Scope a query to only include expired agreements.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry')
            ->where('expiry', '<', now()->toDateString());
    }
}
