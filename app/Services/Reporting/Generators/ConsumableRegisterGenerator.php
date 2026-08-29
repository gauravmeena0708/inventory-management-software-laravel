<?php

namespace App\Services\Reporting\Generators;

use App\Contracts\ReportGeneratorInterface;
use App\Models\Consumable;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\StockBalance;
use App\Models\User;

class ConsumableRegisterGenerator implements ReportGeneratorInterface
{
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array {
        $balances = StockBalance::query()
            ->with(['consumable', 'location'])
            ->whereHas('location.site.organizationalUnits', fn ($q) => $q->where('organizational_units.id', $unit->id))
            ->get();

        $rows = $balances->map(fn (StockBalance $b) => [
            'consumable_id' => $b->consumable_id,
            'consumable_name' => $b->consumable?->name,
            'sku' => $b->consumable?->sku,
            'location_name' => $b->location?->name,
            'current_balance' => (int) $b->quantity,
            'unit' => $b->consumable?->unit ?? 'Units',
        ])->all();

        $totalUnits = array_sum(array_column($rows, 'current_balance'));
        $totalItems = count($rows);

        return [
            'report_code' => $definition->code,
            'unit_name' => $unit->name,
            'unit_code' => $unit->code,
            'scope_type' => $scopeType,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_stock_lines' => $totalItems,
                'total_units_in_stock' => $totalUnits,
            ],
            'rows' => $rows,
        ];
    }
}
