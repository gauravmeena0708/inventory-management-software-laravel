<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Attachment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by',
    ];

    /**
     * Private storage internals must never be serialized into API responses.
     * Downloads are served through an authorized application route.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'disk',
        'path',
        'checksum',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_by' => 'integer',
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
     * Get the owning attachable model.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope attachments through an explicitly supported visible parent.
     * Unknown attachable types fail closed until they receive an ownership rule.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visibilityQuery) use ($user): void {
            $visibilityQuery
                ->where(function (Builder $assetQuery) use ($user): void {
                    $assetQuery
                        ->where('attachable_type', Asset::class)
                        ->whereIn(
                            'attachable_id',
                            Asset::query()->visibleTo($user)->select('assets.id')
                        );
                })
                ->orWhere(function (Builder $locationQuery) use ($user): void {
                    $locationQuery
                        ->where('attachable_type', Location::class)
                        ->whereIn(
                            'attachable_id',
                            Location::query()->visibleTo($user)->select('locations.id')
                        );
                })
                ->orWhere(function (Builder $fileQuery) use ($user): void {
                    $fileQuery
                        ->whereIn('attachable_type', [FileRecord::class, File::class])
                        ->whereIn('attachable_id', FileRecord::query()->visibleTo($user)->select('files.id'));
                })
                ->orWhere(function (Builder $agreementQuery) use ($user): void {
                    $agreementQuery
                        ->where('attachable_type', Agreement::class)
                        ->whereIn('attachable_id', Agreement::query()->visibleTo($user)->select('agreements.id'));
                })
                ->orWhere(function (Builder $taskQuery) use ($user): void {
                    $taskQuery
                        ->where('attachable_type', Task::class)
                        ->whereIn('attachable_id', Task::query()->visibleTo($user)->select('tasks.id'));
                })
                ->orWhere(function (Builder $mapQuery) use ($user): void {
                    $mapQuery
                        ->where('attachable_type', SpatialMap::class)
                        ->whereIn(
                            'attachable_id',
                            SpatialMap::query()
                                ->whereHas('location', fn (Builder $locationQuery): Builder => $locationQuery->visibleTo($user))
                                ->select('spatial_maps.id')
                        );
                });
        });
    }
}
