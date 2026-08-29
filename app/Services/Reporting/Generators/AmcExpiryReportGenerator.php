<?php

namespace App\Services\Reporting\Generators;

use App\Contracts\ReportGeneratorInterface;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;

class AmcExpiryReportGenerator implements ReportGeneratorInterface
{
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array {
        $daysAhead = (int) ($parameters['days_ahead'] ?? 90);
        $cutoffDate = now()->addDays($daysAhead)->toDateString();

        $assetQuery = Asset::query()
            ->with(['category', 'agreements'])
            ->whereNotNull('warranty_expiry')
            ->where('warranty_expiry', '<=', $cutoffDate)
            ->whereNotIn('status', ['disposed', 'decommissioned']);

        if ($scopeType === 'descendants') {
            $assetQuery = app(OrganizationalVisibility::class)->apply($assetQuery, $user);
        } else {
            $assetQuery->where('organizational_unit_id', $unit->id);
        }

        $assets = $assetQuery->get();

        $rows = $assets->map(fn (Asset $a) => [
            'asset_id' => $a->id,
            'asset_tag' => $a->asset_tag,
            'name' => $a->name,
            'category' => $a->category?->name ?? 'General',
            'warranty_expiry' => $a->warranty_expiry?->toDateString(),
            'is_expired' => $a->warranty_expiry?->isPast() ?? false,
            'active_agreements_count' => $a->agreements->count(),
        ])->all();

        return [
            'report_code' => $definition->code,
            'unit_name' => $unit->name,
            'scope_type' => $scopeType,
            'days_ahead' => $daysAhead,
            'summary' => [
                'total_expiring_assets' => count($rows),
                'already_expired_count' => count(array_filter($rows, fn ($r) => $r['is_expired'])),
            ],
            'rows' => $rows,
        ];
    }
}
