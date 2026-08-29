<?php

namespace App\Models;

use App\Enums\AcquisitionType;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Acquisition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'organizational_unit_id',
        'acquisition_type',
        'vendor_name',
        'vendor_id',
        'gem_order_number',
        'purchase_order_number',
        'sanction_reference',
        'invoice_number',
        'invoice_date',
        'grn_number',
        'receipt_date',
        'total_value',
        'currency',
        'file_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_type' => AcquisitionType::class,
            'invoice_date' => 'date',
            'receipt_date' => 'date',
            'total_value' => 'decimal:2',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'vendor_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class, 'file_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'acquisition_assets')
            ->withPivot(['id', 'unit_cost', 'quantity_component'])
            ->withTimestamps();
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
