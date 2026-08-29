<?php

namespace App\Services\Reporting\Generators;

use App\Contracts\ReportGeneratorInterface;
use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;

class AssetRegisterGenerator implements ReportGeneratorInterface
{
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array {
        $query = Asset::query()->with(['category', 'manufacturer', 'location', 'assignedOfficial', 'agreements']);

        if ($scopeType === 'descendants') {
            $query = app(OrganizationalVisibility::class)->apply($query, $user);
        } else {
            $query->where('organizational_unit_id', $unit->id);
        }

        if (! empty($parameters['category_id'])) {
            $query->where('asset_category_id', $parameters['category_id']);
        }

        if (! empty($parameters['status'])) {
            $query->where('status', $parameters['status']);
        }

        $assets = $query->orderBy('asset_tag')->get();

        $rows = $assets->map(fn (Asset $a) => [
            'id' => $a->id,
            'asset_tag' => $a->asset_tag,
            'name' => $a->name,
            'category' => $a->category?->name ?? $a->asset_type?->label() ?? 'Other',
            'manufacturer' => $a->manufacturer?->name ?? $a->manufacturer_name_legacy,
            'serial_number' => $a->serial_number,
            'location' => $a->location?->name,
            'custodian' => $a->assignedOfficial?->name,
            'status' => $a->status->value,
            'purchase_cost' => (float) $a->purchase_cost,
            'purchase_date' => $a->purchase_date?->toDateString(),
            'warranty_expiry' => $a->warranty_expiry?->toDateString(),
            'active_agreements_count' => $a->agreements->count(),
        ])->all();

        $totalCost = $assets->sum('purchase_cost');
        $statusCounts = $assets->groupBy('status.value')->map->count()->all();

        return [
            'report_code' => $definition->code,
            'unit_name' => $unit->name,
            'unit_code' => $unit->code,
            'scope_type' => $scopeType,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_assets' => count($rows),
                'total_valuation' => (float) $totalCost,
                'status_breakdown' => $statusCounts,
            ],
            'rows' => $rows,
        ];
    }
}
