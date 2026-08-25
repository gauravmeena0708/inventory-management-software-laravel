<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agreement_id',
        'amount',
        'currency',
        'due_date',
        'paid_date',
        'status',
        'invoice_number',
        'remarks',
        'schedule_key',
        'completed_by',
        'legacy_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'due_date' => 'date',
            'paid_date' => 'date',
            'amount' => 'decimal:2',
            'legacy_id' => 'integer',
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
     * Get the agreement this payment belongs to.
     */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    /**
     * Get the user who completed / recorded this payment.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING->value);
    }

    /**
     * Scope a query to only include overdue pending payments.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING->value)
            ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only include completed payments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::COMPLETED->value);
    }
}
