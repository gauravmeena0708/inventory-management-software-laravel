<?php

namespace App\Models;

use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class FileRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'efile_number',
        'physical_name',
        'physical_number',
        'subject',
        'division',
        'opened_at',
        'legacy_id',
        'organizational_unit_id',
        // Legacy column compatibility
        'efile',
        'physical',
        'pnumber',
        'pfile',
        'opened',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'date',
            'opened' => 'date',
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
     * Get all attachments associated with this file record.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(OrganizationalVisibility::class)->apply($query, $user);
    }
}
