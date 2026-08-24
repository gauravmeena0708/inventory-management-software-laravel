<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Official extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'designation',
        'department',
        'email',
        'phone',
        'location_id',
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
     * Get the location associated with the official.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get all assets assigned to this official.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_official_id');
    }

    /**
     * Get all assets currently assigned to this official (alias for backward compatibility).
     */
    public function assignedAssets(): HasMany
    {
        return $this->assets();
    }

    /**
     * Get all assignment history records for this official.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'official_id');
    }

    /**
     * Get all stock issue entries associated with this official.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'recipient_official_id');
    }
}
