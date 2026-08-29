<?php

namespace App\Services\Transfers;

use App\Enums\AssetStatus;
use App\Enums\TransferStatus;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectAssetTransferAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Reject or cancel a transfer, returning the asset to IN_STOCK at the source unit.
     */
    public function execute(AssetTransfer $transfer, string $reason, User $actor): AssetTransfer
    {
        return DB::transaction(function () use ($transfer, $reason, $actor) {
            /** @var AssetTransfer $lockedTransfer */
            $lockedTransfer = AssetTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $lockedTransfer->asset_id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedTransfer->status, [TransferStatus::RECEIVED, TransferStatus::REJECTED, TransferStatus::CANCELLED])) {
                throw ValidationException::withMessages([
                    'status' => "Transfer cannot be rejected in '{$lockedTransfer->status->label()}' status.",
                ]);
            }

            $lockedTransfer->update([
                'status' => TransferStatus::REJECTED,
                'remarks' => trim(($lockedTransfer->remarks ? $lockedTransfer->remarks . "\n" : '') . "[Rejected]: {$reason}"),
            ]);

            // Restore asset status if it was in transit
            if ($lockedAsset->status === AssetStatus::IN_TRANSIT) {
                $lockedAsset->update(['status' => AssetStatus::IN_STOCK]);
            }

            return $lockedTransfer->fresh();
        });
    }
}
