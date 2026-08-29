<?php

namespace App\Services\Audit;

use App\Enums\AuditStatus;
use App\Enums\AuditType;
use App\Models\AuditEngagement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAuditEngagementAction
{
    /**
     * Create an audit engagement for an organizational unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $creator): AuditEngagement
    {
        return DB::transaction(function () use ($data, $creator) {
            $type = $data['audit_type'] ?? AuditType::INTERNAL_AUDIT;
            if (is_string($type)) {
                $type = AuditType::from($type);
            }

            $auditNumber = $data['audit_number'] ?? 'AUD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

            return AuditEngagement::create([
                'audit_number' => $auditNumber,
                'audit_type' => $type,
                'audited_organizational_unit_id' => $data['audited_organizational_unit_id'],
                'auditing_organizational_unit_id' => $data['auditing_organizational_unit_id'] ?? null,
                'audit_from' => $data['audit_from'] ?? now()->toDateString(),
                'audit_to' => $data['audit_to'] ?? now()->addMonth()->toDateString(),
                'fieldwork_started_at' => now(),
                'status' => AuditStatus::IN_PROGRESS,
                'lead_auditor_user_id' => $data['lead_auditor_user_id'] ?? $creator->id,
                'created_by' => $creator->id,
                'scope_text' => $data['scope_text'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }
}
