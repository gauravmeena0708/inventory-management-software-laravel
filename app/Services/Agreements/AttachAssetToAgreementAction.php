<?php

namespace App\Services\Agreements;

use App\Enums\AgreementCoverageType;
use App\Enums\LifecycleEventType;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class AttachAssetToAgreementAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Map an asset to an agreement with coverage terms.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Agreement $agreement, Asset $asset, array $attributes = [], ?User $actor = null): void
    {
        DB::transaction(function () use ($agreement, $asset, $attributes, $actor) {
            $coverageType = $attributes['coverage_type'] ?? AgreementCoverageType::AMC->value;
            if ($coverageType instanceof AgreementCoverageType) {
                $coverageType = $coverageType->value;
            }

            $agreement->assets()->syncWithoutDetaching([
                $asset->id => [
                    'coverage_type' => $coverageType,
                    'coverage_start' => $attributes['coverage_start'] ?? $agreement->billing_anchor_date,
                    'coverage_end' => $attributes['coverage_end'] ?? $agreement->expiry,
                    'sla_reference' => $attributes['sla_reference'] ?? null,
                    'remarks' => $attributes['remarks'] ?? null,
                ],
            ]);

            // Emit AGREEMENT_ATTACHED lifecycle event
            $this->recordLifecycleEvent->execute(
                $asset,
                LifecycleEventType::AGREEMENT_ATTACHED,
                $actor,
                [
                    'reference_type' => 'Agreement',
                    'reference_id' => $agreement->id,
                    'reference_number' => $agreement->name,
                    'remarks' => "Covered under agreement: {$agreement->name} ({$coverageType})",
                    'metadata' => [
                        'agreement_id' => $agreement->id,
                        'agreement_name' => $agreement->name,
                        'coverage_type' => $coverageType,
                    ],
                ]
            );
        });
    }
}
