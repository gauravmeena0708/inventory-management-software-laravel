<?php

namespace App\Services\Audit;

use App\Models\AuditObservation;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;

class AuditAgingMetricsService
{
    /**
     * Calculate open observation aging metrics and risk distribution.
     *
     * @return array<string, mixed>
     */
    public function getAgingPosition(OrganizationalUnit $unit, ?User $user = null): array
    {
        $query = AuditObservation::query()
            ->with(['engagement'])
            ->whereHas('engagement', function ($q) use ($unit, $user) {
                $q->where('audited_organizational_unit_id', $unit->id);
                if ($user) {
                    $q = app(OrganizationalVisibility::class)->apply($q, $user);
                }
            })
            ->whereNotIn('status', ['settled', 'dropped']);

        $openObs = $query->get();
        $now = now();

        $lt30 = 0;
        $d30to90 = 0;
        $d90to180 = 0;
        $gt180 = 0;

        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;

        $totalFinancialExposure = 0;

        foreach ($openObs as $obs) {
            $ageDays = $obs->issued_at ? (int) $obs->issued_at->diffInDays($now) : 0;

            if ($ageDays < 30) {
                $lt30++;
            } elseif ($ageDays <= 90) {
                $d30to90++;
            } elseif ($ageDays <= 180) {
                $d90to180++;
            } else {
                $gt180++;
            }

            match ($obs->severity->value) {
                'critical' => $criticalCount++,
                'high' => $highCount++,
                'medium' => $mediumCount++,
                'low' => $lowCount++,
            };

            $totalFinancialExposure += (float) $obs->financial_implication;
        }

        return [
            'unit_id' => $unit->id,
            'unit_name' => $unit->name,
            'total_open_observations' => $openObs->count(),
            'total_financial_exposure' => $totalFinancialExposure,
            'aging_buckets' => [
                'less_than_30_days' => $lt30,
                '30_to_90_days' => $d30to90,
                '90_to_180_days' => $d90to180,
                'greater_than_180_days' => $gt180,
            ],
            'severity_breakdown' => [
                'critical' => $criticalCount,
                'high' => $highCount,
                'medium' => $mediumCount,
                'low' => $lowCount,
            ],
        ];
    }
}
