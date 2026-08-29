<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface AuditRecorder
{
    /**
     * Record an audit activity log entry.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(string $event, ?Model $subject = null, array $properties = [], ?User $actor = null): void;
}
