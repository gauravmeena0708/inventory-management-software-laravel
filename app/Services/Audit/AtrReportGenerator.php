<?php

namespace App\Services\Audit;

use App\Models\AuditEngagement;
use App\Models\AuditObservation;

class AtrReportGenerator
{
    /**
     * Generate structured Action Taken Report (ATR) dataset for an audit engagement.
     *
     * @return array<string, mixed>
     */
    public function generate(AuditEngagement $engagement): array
    {
        $observations = $engagement->observations()
            ->with(['assets', 'responses.submitter', 'evidences'])
            ->orderBy('para_number')
            ->get();

        $paras = $observations->map(fn (AuditObservation $obs) => [
            'id' => $obs->id,
            'para_number' => $obs->para_number,
            'title' => $obs->title,
            'finding' => $obs->finding,
            'severity' => $obs->severity->value,
            'status' => $obs->status->value,
            'status_label' => $obs->status->label(),
            'financial_implication' => (float) $obs->financial_implication,
            'linked_assets' => $obs->assets->map(fn ($a) => [
                'id' => $a->id,
                'asset_tag' => $a->asset_tag,
                'name' => $a->name,
            ])->all(),
            'responses_count' => $obs->responses->count(),
            'latest_office_reply' => $obs->responses->where('response_type.value', 'office_reply')->last()?->body,
            'latest_auditor_remark' => $obs->responses->where('response_type.value', 'auditor_remark')->last()?->body,
            'closure_reason' => $obs->closure_reason,
            'is_settled' => in_array($obs->status->value, ['settled', 'dropped']),
        ])->all();

        $totalParas = count($paras);
        $settledParas = count(array_filter($paras, fn ($p) => $p['is_settled']));
        $outstandingParas = $totalParas - $settledParas;

        return [
            'audit_number' => $engagement->audit_number,
            'audit_type' => $engagement->audit_type->label(),
            'audited_unit_name' => $engagement->auditedOrganizationalUnit->name,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_paras' => $totalParas,
                'settled_paras' => $settledParas,
                'outstanding_paras' => $outstandingParas,
                'compliance_percentage' => $totalParas > 0 ? (int) round(($settledParas / $totalParas) * 100) : 100,
            ],
            'paras' => $paras,
        ];
    }
}
