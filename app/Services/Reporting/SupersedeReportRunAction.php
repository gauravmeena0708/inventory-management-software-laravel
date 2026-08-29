<?php

namespace App\Services\Reporting;

use App\Contracts\ReportGeneratorInterface;
use App\Enums\ReportRunStatus;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupersedeReportRunAction
{
    /**
     * Supersede an existing finalized report run with a new version without silently overwriting history.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function execute(
        ReportRun $originalRun,
        array $parameters,
        string $supersedeReason,
        User $actor
    ): ReportRun {
        return DB::transaction(function () use ($originalRun, $parameters, $supersedeReason, $actor) {
            /** @var ReportRun $lockedOriginal */
            $lockedOriginal = ReportRun::where('id', $originalRun->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedOriginal->status, [ReportRunStatus::FINAL, ReportRunStatus::GENERATED])) {
                throw ValidationException::withMessages([
                    'status' => "Cannot supersede a report in status '{$lockedOriginal->status->label()}'.",
                ]);
            }

            $definition = $lockedOriginal->definition;
            /** @var ReportGeneratorInterface $generator */
            $generator = app($definition->generator_class);

            $mergedParameters = array_merge($lockedOriginal->parameters ?? [], $parameters);

            $newSnapshotData = $generator->generate(
                $definition,
                $lockedOriginal->organizationalUnit,
                $mergedParameters,
                $actor,
                $lockedOriginal->scope_type
            );

            $rawJson = json_encode($newSnapshotData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $sha256 = hash('sha256', $rawJson);

            $newVersion = $lockedOriginal->version + 1;

            // Create new superseded version
            $newRun = ReportRun::create([
                'report_definition_id' => $definition->id,
                'organizational_unit_id' => $lockedOriginal->organizational_unit_id,
                'scope_type' => $lockedOriginal->scope_type,
                'as_on_date' => $mergedParameters['as_on_date'] ?? $lockedOriginal->as_on_date,
                'data_cutoff_at' => now(),
                'parameters' => $mergedParameters,
                'status' => ReportRunStatus::GENERATED,
                'generated_by' => $actor->id,
                'generated_at' => now(),
                'version' => $newVersion,
                'snapshot_data' => $newSnapshotData,
                'sha256' => $sha256,
                'supersedes_report_run_id' => $lockedOriginal->id,
                'remarks' => "Supersedes Run #{$lockedOriginal->id} (v{$lockedOriginal->version}). Reason: {$supersedeReason}",
            ]);

            // Mark old run as SUPERSEDED
            $lockedOriginal->update([
                'status' => ReportRunStatus::SUPERSEDED,
                'remarks' => trim(($lockedOriginal->remarks ? $lockedOriginal->remarks . "\n" : '') . "[Superseded Note]: Superseded by Run #{$newRun->id} (v{$newVersion})"),
            ]);

            return $newRun;
        });
    }
}
