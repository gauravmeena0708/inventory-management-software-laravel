<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Agreement extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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
