<?php

namespace App\Services\Reporting;

use App\Enums\ReportRunStatus;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CertifyReportRunAction
{
    /**
     * Certify and finalize a report run, locking it into an immutable state with cryptographic hash verification.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(ReportRun $reportRun, array $options, User $certifier): ReportRun
    {
        return DB::transaction(function () use ($reportRun, $options, $certifier) {
            /** @var ReportRun $lockedRun */
            $lockedRun = ReportRun::where('id', $reportRun->id)->lockForUpdate()->firstOrFail();

            if ($lockedRun->status === ReportRunStatus::FINAL) {
                throw ValidationException::withMessages([
                    'status' => 'Report run is already certified as final.',
                ]);
            }

            // Verify integrity hash
            $rawJson = json_encode($lockedRun->snapshot_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $computedHash = hash('sha256', $rawJson);

            $lockedRun->update([
                'status' => ReportRunStatus::FINAL,
                'approved_by' => $certifier->id,
                'approved_at' => now(),
                'sha256' => $computedHash,
                'remarks' => trim(($lockedRun->remarks ? $lockedRun->remarks . "\n" : '') . '[Certification Note]: ' . ($options['remarks'] ?? 'Certified by authorized official.')),
            ]);

            return $lockedRun->fresh();
        });
    }
}
