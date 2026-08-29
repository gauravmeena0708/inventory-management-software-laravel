<?php

namespace App\Models;

use App\Enums\SpatialMapType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SpatialMap extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'location_id',
        'attachment_id',
        'map_type',
        'version',
        'width',
        'height',
        'scale',
        'origin_x',
        'origin_y',
        'origin_z',
        'rotation',
        'coordinate_system',
        'calibration',
        'is_current',
        'uploaded_by',
    ];

    /**
     * Attachment identifiers are implementation details. File access always
     * goes through an authorized spatial-map route.
     *
     * @var array<int, string>
     */
    protected $hidden = ['attachment_id'];

    protected function casts(): array
    {
        return [
            'map_type' => SpatialMapType::class,
            'version' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'scale' => 'decimal:6',
            'origin_x' => 'decimal:6',
            'origin_y' => 'decimal:6',
            'origin_z' => 'decimal:6',
            'rotation' => 'decimal:6',
            'calibration' => 'array',
            'is_current' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
