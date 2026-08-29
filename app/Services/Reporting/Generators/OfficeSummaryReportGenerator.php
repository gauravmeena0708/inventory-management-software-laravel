<?php

namespace App\Services\Reporting\Generators;

use App\Contracts\ReportGeneratorInterface;
use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;

class OfficeSummaryReportGenerator implements ReportGeneratorInterface
{
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array {
        // Collect subordinate units visible to the user
        $subordinateUnits = OrganizationalUnit::query()
            ->where('path', 'like', $unit->path . '%')
            ->where('is_active', true)
            ->get();

        $rows = [];

        foreach ($subordinateUnits as $subUnit) {
            $assets = Asset::where('organizational_unit_id', $subUnit->id)->get();
            $total = $assets->count();
            $inUse = $assets->where('status.value', 'in_use')->count();
            $inStock = $assets->where('status.value', 'in_stock')->count();
            $underMnt = $assets->where('status.value', 'under_maintenance')->count();
            $verified = $assets->filter(fn ($a) => $a->verificationItems()->where('result', 'verified')->exists())->count();

            $rows[] = [
                'unit_id' => $subUnit->id,
                'unit_code' => $subUnit->code,
                'unit_name' => $subUnit->name,
                'unit_type' => $subUnit->unit_type?->value ?? 'OFFICE',
                'total_assets' => $total,
                'in_use' => $inUse,
                'in_stock' => $inStock,
                'under_maintenance' => $underMnt,
                'verified_assets' => $verified,
                'verification_percentage' => $total > 0 ? (int) round(($verified / $total) * 100) : 100,
            ];
        }

        $totalAll = array_sum(array_column($rows, 'total_assets'));

        return [
            'report_code' => $definition->code,
            'parent_unit_name' => $unit->name,
            'parent_unit_code' => $unit->code,
            'summary' => [
                'total_offices' => count($rows),
                'total_consolidated_assets' => $totalAll,
            ],
            'rows' => $rows,
        ];
    }
}
