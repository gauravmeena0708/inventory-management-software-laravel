<?php

namespace App\Services\Audit;

use App\Enums\AuditResponseType;
use App\Enums\ObservationStatus;
use App\Models\AuditObservation;
use App\Models\AuditResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitAuditResponseAction
{
    /**
     * Submit an official response or clarification to an audit observation.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AuditObservation $observation, array $data, User $actor): AuditResponse
    {
        return DB::transaction(function () use ($observation, $data, $actor) {
            $responseType = $data['response_type'] ?? AuditResponseType::OFFICE_REPLY;
            if (is_string($responseType)) {
                $responseType = AuditResponseType::from($responseType);
            }

            $response = AuditResponse::create([
                'audit_observation_id' => $observation->id,
                'response_type' => $responseType,
                'organizational_unit_id' => $data['organizational_unit_id'] ?? $observation->engagement->audited_organizational_unit_id,
                'submitted_by' => $actor->id,
                'body' => $data['body'],
                'submitted_at' => now(),
                'attachment_id' => $data['attachment_id'] ?? null,
            ]);

            // Update observation state
            if ($responseType === AuditResponseType::OFFICE_REPLY) {
                $observation->update(['status' => ObservationStatus::REPLY_RECEIVED]);
            } elseif ($responseType === AuditResponseType::AUDITOR_REMARK) {
                $observation->update(['status' => ObservationStatus::UNDER_EXAMINATION]);
            }

            return $response;
        });
    }
}
