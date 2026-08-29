<?php

namespace App\Services\Reporting\Generators;

use App\Contracts\ReportGeneratorInterface;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\DataQuality\InventoryDataQualityService;

class DataQualityReportGenerator implements ReportGeneratorInterface
{
    public function generate(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $user,
        string $scopeType = 'local'
    ): array {
        $dqService = app(InventoryDataQualityService::class);
        $evaluation = $dqService->evaluateUnit($unit, $user);

        return [
            'report_code' => $definition->code,
            'unit_name' => $unit->name,
            'unit_code' => $unit->code,
            'scope_type' => $scopeType,
            'summary' => $evaluation,
        ];
    }
}
