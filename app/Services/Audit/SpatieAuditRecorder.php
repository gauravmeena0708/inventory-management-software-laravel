<?php

namespace App\Services\Audit;

use App\Contracts\AuditRecorder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SpatieAuditRecorder implements AuditRecorder
{
    /**
     * Record an audit activity log entry.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(string $event, ?Model $subject = null, array $properties = [], ?User $actor = null): void
    {
        $logger = activity();

        if ($event !== '') {
            $logger->event($event);
        }

        if ($subject !== null) {
            $logger->performedOn($subject);
        }

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        if (!empty($properties)) {
            $logger->withProperties($properties);
        }

        $logger->log($event);
    }
}
