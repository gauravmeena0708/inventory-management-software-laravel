<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvidence extends Model
{
    use HasFactory;

    protected $table = 'audit_evidence';

    protected $fillable = [
        'audit_observation_id',
        'attachment_id',
        'evidence_type',
        'description',
        'source_model_type',
        'source_model_id',
        'created_by',
    ];

    public function observation(): BelongsTo
    {
        return $this->belongsTo(AuditObservation::class, 'audit_observation_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
