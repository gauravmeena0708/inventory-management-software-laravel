<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Asset extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'legacy_source',
        'legacy_id',
        'asset_tag',
        'name',
        'asset_type',
        'asset_category_id',
        'manufacturer_id',
        'manufacturer_name_legacy',
        'location_id',
        'location_text_legacy',
        'assigned_official_id',
        'status',
        'serial_number',
        'model_number',
        'part_code',
        'ip_address',
        'mac_address',
        'operating_system',
        'description',
        'specifications',
        'purchase_date',
        'purchase_cost',
        'currency',
        'warranty_expiry',
        'end_of_sale',
        'end_of_support',
        'amc_start',
        'amc_end',
        'amc_cost',
        'contract_type',
        'contract_reference',
        'file_id',
        'legacy_file_reference',
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
            'asset_type' => AssetType::class,
            'status' => AssetStatus::class,
            'specifications' => 'array',
            'legacy_payload' => 'array',
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'warranty_expiry' => 'date',
            'end_of_sale' => 'date',
            'end_of_support' => 'date',
            'amc_start' => 'date',
            'amc_end' => 'date',
            'amc_cost' => 'decimal:2',
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
     * Get the category of the asset.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /**
     * Get the manufacturer of the asset.
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Get the location of the asset.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the organizational unit of the asset.
     */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /**
     * Get the official currently assigned to this asset.
     */
    public function assignedOfficial(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'assigned_official_id');
    }

    /**
     * Get the file registry record associated with this asset.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class, 'file_id');
    }

    /**
     * Get all assignment history records for this asset.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

    /**
     * Get the current (open) assignment for this asset.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class, 'asset_id')
            ->whereNull('returned_at')
            ->latestOfMany('assigned_at');
    }

    /**
     * Get all attachments associated with this asset.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all placement history records for this asset.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(AssetPlacement::class, 'asset_id');
    }

    /**
     * Get the current (open) placement for this asset.
     */
    public function currentPlacement(): HasOne
    {
        return $this->hasOne(AssetPlacement::class, 'asset_id')
            ->where('open_marker', true)
            ->whereNull('removed_at')
            ->latestOfMany('placed_at');
    }

    /**
     * Get the unified lifecycle event stream for this asset.
     */
    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(AssetLifecycleEvent::class, 'asset_id')->orderBy('occurred_at', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Get all agreements covering this asset.
     */
    public function agreements(): BelongsToMany
    {
        return $this->belongsToMany(Agreement::class, 'agreement_asset')
            ->withPivot(['id', 'coverage_type', 'coverage_start', 'coverage_end', 'sla_reference', 'remarks'])
            ->withTimestamps();
    }

    /**
     * Get all maintenance tickets for this asset.
     */
    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'asset_id')->latest('reported_at');
    }

    /**
     * Get all transfers for this asset.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'asset_id')->latest('requested_at');
    }

    /**
     * Get all disposal records for this asset.
     */
    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class, 'asset_id')->latest('created_at');
    }

    /**
     * Get current active disposal record.
     */
    public function currentDisposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class, 'asset_id')->latestOfMany('created_at');
    }

    /**
     * Get verification history items for this asset.
     */
    public function verificationItems(): HasMany
    {
        return $this->hasMany(InventoryVerificationItem::class, 'asset_id')->latest('verified_at');
    }

    /**
     * Get acquisitions providing provenance for this asset.
     */
    public function acquisitions(): BelongsToMany
    {
        return $this->belongsToMany(Acquisition::class, 'acquisition_assets')
            ->withPivot(['id', 'unit_cost', 'quantity_component'])
            ->withTimestamps();
    }

    /**
     * Child relationships where this asset is the parent.
     */
    public function childRelationships(): HasMany
    {
        return $this->hasMany(AssetRelationship::class, 'parent_asset_id');
    }

    /**
     * Parent relationships where this asset is the child.
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(AssetRelationship::class, 'child_asset_id');
    }

    /**
     * Child assets connected to or installed in this asset.
     */
    public function childAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_relationships', 'parent_asset_id', 'child_asset_id')
            ->withPivot(['id', 'relationship_type', 'remarks'])
            ->withTimestamps();
    }

    /**
     * Parent assets that contain or power this asset.
     */
    public function parentAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_relationships', 'child_asset_id', 'parent_asset_id')
            ->withPivot(['id', 'relationship_type', 'remarks'])
            ->withTimestamps();
    }

    /**
     * Alerts generated for this asset.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class, 'asset_id');
    }

    /**
     * Scope assets to organizational units visible to an explicit user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }

    /**
     * Scope a query to only include assets of a given category.
     */
    public function scopeCategory(Builder $query, int|string $categoryId): Builder
    {
        return $query->where('asset_category_id', $categoryId);
    }

    /**
     * Scope a query to only include assets of a given type.
     */
    public function scopeType(Builder $query, AssetType|string $type): Builder
    {
        $value = $type instanceof AssetType ? $type->value : $type;

        return $query->where('asset_type', $value);
    }

    /**
     * Scope a query to only include assets of a given status.
     */
    public function scopeStatus(Builder $query, AssetStatus|string $status): Builder
    {
        $value = $status instanceof AssetStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * Scope a query to only include assets that are in use.
     */
    public function scopeInUse(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::IN_USE->value);
    }

    /**
     * Scope a query to only include assets that are in stock.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::IN_STOCK->value);
    }

    /**
     * Scope a query to only include assets that are decommissioned.
     */
    public function scopeDecommissioned(Builder $query): Builder
    {
        return $query->where('status', AssetStatus::DECOMMISSIONED->value);
    }

    /**
     * Scope a query to only include assets with AMC expiring within a number of days.
     */
    public function scopeExpiringAmc(Builder $query, int $days = 180): Builder
    {
        return $query->whereNotNull('amc_end')
            ->whereBetween('amc_end', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    /**
     * Scope a query to only include assets with warranty expiring within a number of days.
     */
    public function scopeExpiringWarranty(Builder $query, int $days = 180): Builder
    {
        return $query->whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    /**
     * Scope a query to only include assets with support expiring within a number of days.
     */
    public function scopeExpiringSupport(Builder $query, int $days = 180): Builder
    {
        return $query->whereNotNull('end_of_support')
            ->whereBetween('end_of_support', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    /**
     * Scope a query to search assets by tag, serial number, name, or model number.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('asset_tag', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhere('model_number', 'like', "%{$term}%")
                ->orWhere('part_code', 'like', "%{$term}%");
        });
    }
}
