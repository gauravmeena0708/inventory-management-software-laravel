<?php

namespace App\Services\Reporting;

use App\Contracts\ReportGeneratorInterface;
use App\Enums\ReportRunStatus;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GenerateReportRunAction
{
    /**
     * Execute a report generator and create a persisted, hash-verified ReportRun.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function execute(
        ReportDefinition $definition,
        OrganizationalUnit $unit,
        array $parameters,
        User $actor,
        string $scopeType = 'local'
    ): ReportRun {
        return DB::transaction(function () use ($definition, $unit, $parameters, $actor, $scopeType) {
            /** @var ReportGeneratorInterface $generator */
            $generator = app($definition->generator_class);

            $snapshotData = $generator->generate(
                $definition,
                $unit,
                $parameters,
                $actor,
                $scopeType
            );

            // Compute SHA-256 integrity hash of serialized snapshot data
            $rawJson = json_encode($snapshotData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $sha256 = hash('sha256', $rawJson);

            $reportRun = ReportRun::create([
                'report_definition_id' => $definition->id,
                'organizational_unit_id' => $unit->id,
                'scope_type' => $scopeType,
                'as_on_date' => $parameters['as_on_date'] ?? now()->toDateString(),
                'data_cutoff_at' => now(),
                'parameters' => $parameters,
                'status' => ReportRunStatus::GENERATED,
                'generated_by' => $actor->id,
                'generated_at' => now(),
                'version' => 1,
                'snapshot_data' => $snapshotData,
                'sha256' => $sha256,
            ]);

            return $reportRun;
        });
    }
}
