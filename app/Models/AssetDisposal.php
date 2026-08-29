<?php

namespace App\Models;

use App\Enums\DisposalMethod;
use App\Enums\DisposalStatus;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AssetDisposal extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'asset_id',
        'organizational_unit_id',
        'disposal_number',
        'status',
        'recommendation_date',
        'recommended_by',
        'committee_reference',
        'inspection_reference',
        'approval_date',
        'approved_by',
        'approval_reference',
        'disposal_method',
        'disposal_vendor',
        'auction_reference',
        'disposal_date',
        'sale_value',
        'data_destruction_required',
        'data_destruction_completed_at',
        'data_destruction_certificate_attachment_id',
        'disposal_certificate_attachment_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => DisposalStatus::class,
            'disposal_method' => DisposalMethod::class,
            'recommendation_date' => 'date',
            'approval_date' => 'date',
            'disposal_date' => 'date',
            'sale_value' => 'decimal:2',
            'data_destruction_required' => 'boolean',
            'data_destruction_completed_at' => 'datetime',
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

    public function recommendedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dataDestructionCertificate(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'data_destruction_certificate_attachment_id');
    }

    public function disposalCertificate(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'disposal_certificate_attachment_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }
}
