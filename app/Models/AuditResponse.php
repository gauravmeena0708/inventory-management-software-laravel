<?php

namespace App\Models;

use App\Enums\AuditResponseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_observation_id',
        'response_type',
        'organizational_unit_id',
        'submitted_by',
        'body',
        'submitted_at',
        'attachment_id',
    ];

    protected function casts(): array
    {
        return [
            'response_type' => AuditResponseType::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(AuditObservation::class, 'audit_observation_id');
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }
}
