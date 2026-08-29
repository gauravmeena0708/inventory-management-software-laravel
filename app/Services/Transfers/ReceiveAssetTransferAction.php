<?php

namespace App\Services\Transfers;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Enums\TransferStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetPlacement;
use App\Models\AssetTransfer;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveAssetTransferAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Complete the transfer by receiving the asset at the destination unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AssetTransfer $transfer, array $data, User $actor): AssetTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $actor) {
            /** @var AssetTransfer $lockedTransfer */
            $lockedTransfer = AssetTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $lockedTransfer->asset_id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedTransfer->status, [TransferStatus::IN_TRANSIT, TransferStatus::APPROVED])) {
                throw ValidationException::withMessages([
                    'status' => "Cannot receive a transfer with status '{$lockedTransfer->status->label()}'.",
                ]);
            }

            $receivedAt = $data['received_at'] ?? now();
            $destinationLocationId = $data['to_location_id'] ?? $lockedTransfer->to_location_id;

            // Close any open assignments since ownership transferred
            $openAssignments = AssetAssignment::where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->get();

            foreach ($openAssignments as $openAssignment) {
                $openAssignment->update([
                    'returned_at' => $receivedAt,
                    'return_recorded_by' => $actor->id,
                    'remarks' => trim(($openAssignment->remarks ? $openAssignment->remarks . "\n" : '') . '[Closed on inter-unit transfer]'),
                ]);
            }

            // Close previous placements
            $openPlacements = AssetPlacement::where('asset_id', $lockedAsset->id)
                ->whereNull('removed_at')
                ->get();

            foreach ($openPlacements as $openPlacement) {
                $openPlacement->update([
                    'removed_at' => $receivedAt,
                    'open_marker' => null,
                ]);
            }

            $fromUnitId = $lockedAsset->organizational_unit_id;
            $fromLocationId = $lockedAsset->location_id;

            // Update transfer record
            $lockedTransfer->update([
                'status' => TransferStatus::RECEIVED,
                'received_at' => $receivedAt,
                'received_by' => $actor->id,
                'condition_at_receipt' => $data['condition_at_receipt'] ?? null,
                'to_location_id' => $destinationLocationId,
            ]);

            // Update asset ownership and location
            $lockedAsset->update([
                'organizational_unit_id' => $lockedTransfer->to_organizational_unit_id,
                'location_id' => $destinationLocationId,
                'assigned_official_id' => null,
                'status' => AssetStatus::IN_STOCK,
            ]);

            // If destination location is provided, create a placement record
            if ($destinationLocationId) {
                $lockedAsset->placements()->create([
                    'location_id' => $destinationLocationId,
                    'placed_by' => $actor->id,
                    'placed_at' => $receivedAt,
                    'open_marker' => true,
                    'source' => 'transfer_receipt',
                    'remarks' => "Placed upon transfer receipt (Transfer #{$lockedTransfer->transfer_number})",
                ]);
            }

            // Emit TRANSFER_RECEIVED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::TRANSFER_RECEIVED,
                $actor,
                [
                    'occurred_at' => $receivedAt,
                    'from_status' => AssetStatus::IN_TRANSIT,
                    'to_status' => AssetStatus::IN_STOCK,
                    'from_organizational_unit_id' => $fromUnitId,
                    'to_organizational_unit_id' => $lockedTransfer->to_organizational_unit_id,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => $destinationLocationId,
                    'reference_type' => 'AssetTransfer',
                    'reference_id' => $lockedTransfer->id,
                    'reference_number' => $lockedTransfer->transfer_number,
                    'remarks' => "Transfer received at destination unit #{$lockedTransfer->to_organizational_unit_id}",
                    'metadata' => [
                        'condition_at_receipt' => $data['condition_at_receipt'] ?? null,
                    ],
                ]
            );

            return $lockedTransfer->fresh();
        });
    }
}
