<?php

namespace App\Services\Audit;

use App\Enums\AuditResponseType;
use App\Enums\ObservationStatus;
use App\Models\AuditObservation;
use App\Models\AuditResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SettleAuditObservationAction
{
    /**
     * Settle or drop an audit observation after satisfactory explanation/rectification.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(AuditObservation $observation, array $options, User $auditor): AuditObservation
    {
        return DB::transaction(function () use ($observation, $options, $auditor) {
            $isDropped = ($options['status'] ?? 'settled') === 'dropped';
            $status = $isDropped ? ObservationStatus::DROPPED : ObservationStatus::SETTLED;

            $observation->update([
                'status' => $status,
                'closed_by' => $auditor->id,
                'closed_at' => now(),
                'closure_reason' => $options['remarks'] ?? 'Observation settled after verification of compliance.',
            ]);

            // Add final settlement decision response
            AuditResponse::create([
                'audit_observation_id' => $observation->id,
                'response_type' => AuditResponseType::FINAL_DECISION,
                'organizational_unit_id' => $observation->engagement->audited_organizational_unit_id,
                'submitted_by' => $auditor->id,
                'body' => $options['remarks'] ?? 'Audit para settled by authorized auditor.',
                'submitted_at' => now(),
            ]);

            // Check if all observations in engagement are now settled
            $openRemaining = $observation->engagement->observations()->whereNotIn('status', ['settled', 'dropped'])->exists();
            if (! $openRemaining) {
                $observation->engagement->update(['status' => \App\Enums\AuditStatus::CLOSED]);
            }

            return $observation->fresh();
        });
    }
}
