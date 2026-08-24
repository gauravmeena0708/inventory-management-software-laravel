<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Developer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'reporting_id',
        'category_id',
        'phone',
        'email',
        'salary',
        'status',
        'remarks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
        ];
    }

    /**
     * Get the options for activity logging.
     * Excludes encrypted sensitive fields from plain logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['salary', 'phone', 'email'])
            ->logOnlyDirty();
    }

    /**
     * Get the reporting user/manager for this developer.
     */
    public function reportingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_id');
    }

    /**
     * Alias for reporting user (backward compatibility).
     */
    public function reporting(): BelongsTo
    {
        return $this->reportingUser();
    }

    /**
     * Get the category associated with this developer.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Devcat::class, 'category_id');
    }

    /**
     * Scope a query to only include active developers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'active')
                ->orWhere('status', '1')
                ->orWhere('status', 1);
        });
    }

    /**
     * Scope a query to only include discontinued/inactive developers.
     */
    public function scopeDiscontinued(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'discontinued')
                ->orWhere('status', 'inactive')
                ->orWhere('status', '0')
                ->orWhere('status', 0);
        });
    }
}
