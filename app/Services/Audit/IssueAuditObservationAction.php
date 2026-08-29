<?php

namespace App\Services\Audit;

use App\Enums\ObservationSeverity;
use App\Enums\ObservationStatus;
use App\Models\AuditEngagement;
use App\Models\AuditObservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IssueAuditObservationAction
{
    /**
     * Issue an official audit observation / paragraph against an engagement.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AuditEngagement $engagement, array $data, User $auditor): AuditObservation
    {
        return DB::transaction(function () use ($engagement, $data, $auditor) {
            $severity = $data['severity'] ?? ObservationSeverity::MEDIUM;
            if (is_string($severity)) {
                $severity = ObservationSeverity::from($severity);
            }

            $observation = AuditObservation::create([
                'audit_engagement_id' => $engagement->id,
                'para_number' => $data['para_number'],
                'title' => $data['title'],
                'finding' => $data['finding'],
                'severity' => $severity,
                'risk_category' => $data['risk_category'] ?? 'General Audit Finding',
                'financial_implication' => $data['financial_implication'] ?? null,
                'currency' => $data['currency'] ?? 'INR',
                'status' => ObservationStatus::ISSUED,
                'issued_at' => now(),
                'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'created_by' => $auditor->id,
            ]);

            if (! empty($data['asset_ids'])) {
                $observation->assets()->sync($data['asset_ids']);
            }

            return $observation;
        });
    }
}
