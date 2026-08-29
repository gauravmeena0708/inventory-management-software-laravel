<?php

namespace App\Services\Transfers;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Enums\TransferStatus;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchAssetTransferAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Dispatch the transfer, placing the asset into IN_TRANSIT status.
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

            if (! in_array($lockedTransfer->status, [TransferStatus::PENDING_APPROVAL, TransferStatus::APPROVED])) {
                throw ValidationException::withMessages([
                    'status' => "Cannot dispatch a transfer in '{$lockedTransfer->status->label()}' status.",
                ]);
            }

            $previousStatus = $lockedAsset->status;

            $lockedTransfer->update([
                'status' => TransferStatus::IN_TRANSIT,
                'dispatched_at' => $data['dispatched_at'] ?? now(),
                'condition_at_dispatch' => $data['condition_at_dispatch'] ?? null,
                'approved_by' => $lockedTransfer->approved_by ?? $actor->id,
                'approved_at' => $lockedTransfer->approved_at ?? now(),
            ]);

            // Set asset status to IN_TRANSIT
            $lockedAsset->update(['status' => AssetStatus::IN_TRANSIT]);

            // Emit TRANSFER_DISPATCHED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::TRANSFER_DISPATCHED,
                $actor,
                [
                    'from_status' => $previousStatus,
                    'to_status' => AssetStatus::IN_TRANSIT,
                    'from_organizational_unit_id' => $lockedTransfer->from_organizational_unit_id,
                    'to_organizational_unit_id' => $lockedTransfer->to_organizational_unit_id,
                    'reference_type' => 'AssetTransfer',
                    'reference_id' => $lockedTransfer->id,
                    'reference_number' => $lockedTransfer->transfer_number,
                    'remarks' => "Asset dispatched for transfer to unit #{$lockedTransfer->to_organizational_unit_id}",
                    'metadata' => [
                        'condition_at_dispatch' => $data['condition_at_dispatch'] ?? null,
                    ],
                ]
            );

            return $lockedTransfer->fresh();
        });
    }
}
