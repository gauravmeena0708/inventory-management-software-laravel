<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Location extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sublocation',
        'building',
        'floor',
        'description',
        'seat',
        'point',
        'pin',
        'site_id',
        'parent_id',
        'code',
        'location_type',
        'path',
        'level_number',
        'geometry_geojson',
        'local_x',
        'local_y',
        'local_z',
        'is_restricted',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location_type' => \App\Enums\LocationType::class,
        'geometry_geojson' => 'array',
        'local_x' => 'decimal:2',
        'local_y' => 'decimal:2',
        'local_z' => 'decimal:2',
        'is_restricted' => 'boolean',
        'is_active' => 'boolean',
    ];

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
     * Get the officials assigned to this location.
     */
    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }

    /**
     * Get all attachments associated with this location.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the site that this location belongs to.
     */
    public function site(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the parent location.
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /**
     * Get the children locations.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }
}
