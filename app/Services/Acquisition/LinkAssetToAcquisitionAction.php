<?php

namespace App\Services\Acquisition;

use App\Enums\LifecycleEventType;
use App\Models\Acquisition;
use App\Models\Asset;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class LinkAssetToAcquisitionAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Associate an asset to an acquisition record with provenance details.
     *
     * @param  array<string, mixed>  $pivotData
     */
    public function execute(Acquisition $acquisition, Asset $asset, array $pivotData = [], ?User $actor = null): void
    {
        DB::transaction(function () use ($acquisition, $asset, $pivotData, $actor) {
            $acquisition->assets()->syncWithoutDetaching([
                $asset->id => [
                    'unit_cost' => $pivotData['unit_cost'] ?? $asset->purchase_cost,
                    'quantity_component' => $pivotData['quantity_component'] ?? 1,
                ],
            ]);

            // Emit ACQUIRED lifecycle event
            $this->recordLifecycleEvent->execute(
                $asset,
                LifecycleEventType::ACQUIRED,
                $actor,
                [
                    'reference_type' => 'Acquisition',
                    'reference_id' => $acquisition->id,
                    'reference_number' => $acquisition->gem_order_number ?? $acquisition->purchase_order_number ?? $acquisition->invoice_number,
                    'remarks' => "Acquisition provenance linked: {$acquisition->acquisition_type?->label()}" . ($acquisition->gem_order_number ? " (GeM: {$acquisition->gem_order_number})" : ''),
                    'metadata' => [
                        'acquisition_id' => $acquisition->id,
                        'gem_order_number' => $acquisition->gem_order_number,
                        'invoice_number' => $acquisition->invoice_number,
                    ],
                ]
            );
        });
    }
}
