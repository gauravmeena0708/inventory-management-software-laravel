<?php

namespace App\Services\Transfers;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Enums\TransferStatus;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InitiateAssetTransferAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Initiate a transfer request between organizational units.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Asset $asset, array $data, User $actor): AssetTransfer
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedAsset->status, [AssetStatus::DISPOSED, AssetStatus::IN_TRANSIT])) {
                throw ValidationException::withMessages([
                    'asset_id' => "Cannot initiate transfer for an asset with status '{$lockedAsset->status->label()}'.",
                ]);
            }

            $toUnit = OrganizationalUnit::findOrFail($data['to_organizational_unit_id']);
            if ($toUnit->id === $lockedAsset->organizational_unit_id) {
                throw ValidationException::withMessages([
                    'to_organizational_unit_id' => 'Destination organizational unit must be different from current unit.',
                ]);
            }

            $transferNumber = $data['transfer_number'] ?? 'TRF-' . strtoupper(Str::random(8));

            $transfer = AssetTransfer::create([
                'transfer_number' => $transferNumber,
                'asset_id' => $lockedAsset->id,
                'from_organizational_unit_id' => $lockedAsset->organizational_unit_id,
                'to_organizational_unit_id' => $toUnit->id,
                'from_location_id' => $lockedAsset->location_id,
                'to_location_id' => $data['to_location_id'] ?? null,
                'initiated_by' => $actor->id,
                'status' => TransferStatus::PENDING_APPROVAL,
                'requested_at' => now(),
                'reference_number' => $data['reference_number'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Emit TRANSFER_INITIATED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::TRANSFER_INITIATED,
                $actor,
                [
                    'from_organizational_unit_id' => $lockedAsset->organizational_unit_id,
                    'to_organizational_unit_id' => $toUnit->id,
                    'reference_type' => 'AssetTransfer',
                    'reference_id' => $transfer->id,
                    'reference_number' => $transfer->transfer_number,
                    'remarks' => "Transfer initiated to unit: {$toUnit->name} (Transfer #{$transfer->transfer_number})",
                    'metadata' => [
                        'transfer_number' => $transfer->transfer_number,
                        'from_unit_id' => $lockedAsset->organizational_unit_id,
                        'to_unit_id' => $toUnit->id,
                    ],
                ]
            );

            return $transfer;
        });
    }
}
