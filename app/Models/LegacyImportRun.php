<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegacyImportRun extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'legacy_import_runs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_fingerprint',
        'dry_run',
        'started_at',
        'completed_at',
        'counts',
        'anomalies',
        'status',
        'error_summary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'counts' => 'array',
            'anomalies' => 'array',
        ];
    }

    /**
     * Determine if the import run completed successfully.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['completed', 'dry_run_completed'], true);
    }

    /**
     * Determine if the import run was executed in dry-run mode.
     */
    public function isDryRun(): bool
    {
        return (bool) $this->dry_run;
    }
}
