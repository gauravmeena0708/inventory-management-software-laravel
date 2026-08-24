<?php

namespace App\Services\Importer\TableImporters;

use App\Enums\PaymentStatus;
use App\Models\Agreement;
use App\Models\FileRecord;
use App\Models\Payment;

class AgreementTableImporter extends BaseTableImporter
{
    /**
     * Execute the agreement and payment import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importAgreements($progressCallback);
        $this->importPayments($progressCallback);

        return $this->counts;
    }

    /**
     * Import legacy vendor agreements.
     */
    protected function importAgreements(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('agreements')) {
            return;
        }

        $query = $this->legacyQuery('agreements')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "Agreement #{$legacyId}";
                }

                $fileId = $rowArray['file_id'] ?? null;
                if ($fileId !== null) {
                    $fileExists = FileRecord::on($this->targetConnection)
                        ->where('id', $fileId)
                        ->orWhere('legacy_id', $fileId)
                        ->exists();

                    if (!$fileExists) {
                        $this->recordAnomaly(
                            'agreements',
                            'missing_foreign_key',
                            "Agreement #{$legacyId} references non-existent file ID {$fileId}.",
                            ['agreement_id' => $legacyId, 'file_id' => $fileId]
                        );
                        $fileId = null;
                    }
                }

                $intervalMonths = $this->mapFrequencyToIntervalMonths($rowArray['frequency'] ?? null);

                $attributes = [
                    'name' => $name,
                    'agency' => $rowArray['agency'] ?? 'Unknown Agency',
                    'file_id' => $fileId,
                    'type' => $rowArray['type'] ?? 'AMC',
                    'expiry' => $rowArray['expiry'] ?? null,
                    'annual_cost' => isset($rowArray['annual_cost']) ? (float) $rowArray['annual_cost'] : null,
                    'billing_interval_months' => $intervalMonths,
                    'paid_till' => $rowArray['paid_till'] ?? null,
                    'remarks' => $rowArray['remarks'] ?? null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Agreement::on($this->targetConnection)->updateOrCreate(
                    ['id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('agreements');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('agreements', $processed, $total);
            }
        });
    }

    /**
     * Import legacy payment records.
     */
    protected function importPayments(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('payments')) {
            return;
        }

        $query = $this->legacyQuery('payments')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $agreementId = (int) ($rowArray['agreement_id'] ?? 0);

                // Verify agreement exists
                $agreementExists = Agreement::on($this->targetConnection)->where('id', $agreementId)->exists();
                if (!$agreementExists) {
                    $this->recordAnomaly(
                        'payments',
                        'missing_foreign_key',
                        "Payment #{$legacyId} references non-existent agreement ID {$agreementId}.",
                        ['payment_id' => $legacyId, 'agreement_id' => $agreementId]
                    );
                    continue;
                }

                $pending = (bool) ($rowArray['pending'] ?? true);
                $status = $pending ? PaymentStatus::PENDING : PaymentStatus::COMPLETED;
                $dueDate = $rowArray['due_date'] ?? ($rowArray['created_at'] ?? now()->toDateString());
                $paidDate = $pending ? null : ($rowArray['paid_date'] ?? ($rowArray['updated_at'] ?? $dueDate));

                $remarks = $rowArray['remark'] ?? ($rowArray['name'] ?? null);
                $scheduleKey = "legacy-payment-{$legacyId}";

                $attributes = [
                    'agreement_id' => $agreementId,
                    'amount' => isset($rowArray['amount']) ? (float) $rowArray['amount'] : null,
                    'due_date' => $dueDate,
                    'paid_date' => $paidDate,
                    'status' => $status,
                    'remarks' => $remarks,
                    'schedule_key' => $scheduleKey,
                    'legacy_id' => $legacyId,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Payment::on($this->targetConnection)->updateOrCreate(
                    ['legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('payments');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('payments', $processed, $total);
            }
        });
    }

    /**
     * Map frequency integer or string to interval months.
     */
    protected function mapFrequencyToIntervalMonths(mixed $frequency): ?int
    {
        if ($frequency === null || $frequency === '') {
            return null;
        }

        $val = (int) $frequency;

        return match ($val) {
            1 => 1,   // Monthly
            2 => 2,   // Bi-monthly
            3, 4 => 3, // Quarterly
            6 => 6,   // Semi-annually
            12 => 12, // Annually
            default => $val > 0 && $val <= 12 ? $val : null,
        };
    }
}
