<?php

namespace App\Services\Disposal;

use App\Enums\AssetStatus;
use App\Enums\DisposalStatus;
use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetDisposal;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteAssetDisposalAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Finalize disposal of an asset, updating its status to DISPOSED (not deleted) and recording destruction/disposal certificates.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(AssetDisposal $disposal, array $data, User $actor): AssetDisposal
    {
        return DB::transaction(function () use ($disposal, $data, $actor) {
            /** @var AssetDisposal $lockedDisposal */
            $lockedDisposal = AssetDisposal::where('id', $disposal->id)->lockForUpdate()->firstOrFail();
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $lockedDisposal->asset_id)->lockForUpdate()->firstOrFail();

            if ($lockedDisposal->status === DisposalStatus::DISPOSED) {
                throw ValidationException::withMessages([
                    'status' => 'Asset disposal is already completed.',
                ]);
            }

            $disposalDate = $data['disposal_date'] ?? now()->toDateString();

            // Close any open assignments
            $openAssignments = AssetAssignment::where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->get();

            foreach ($openAssignments as $openAssignment) {
                $openAssignment->update([
                    'returned_at' => now(),
                    'return_recorded_by' => $actor->id,
                    'remarks' => trim(($openAssignment->remarks ? $openAssignment->remarks . "\n" : '') . '[Closed on disposal]'),
                ]);
            }

            $lockedDisposal->update([
                'status' => DisposalStatus::DISPOSED,
                'approval_date' => $lockedDisposal->approval_date ?? now()->toDateString(),
                'approved_by' => $lockedDisposal->approved_by ?? $actor->id,
                'approval_reference' => $data['approval_reference'] ?? $lockedDisposal->approval_reference,
                'disposal_method' => $data['disposal_method'] ?? $lockedDisposal->disposal_method,
                'disposal_vendor' => $data['disposal_vendor'] ?? $lockedDisposal->disposal_vendor,
                'auction_reference' => $data['auction_reference'] ?? $lockedDisposal->auction_reference,
                'disposal_date' => $disposalDate,
                'sale_value' => $data['sale_value'] ?? $lockedDisposal->sale_value,
                'data_destruction_completed_at' => $data['data_destruction_completed_at'] ?? ($lockedDisposal->data_destruction_required ? now() : null),
                'data_destruction_certificate_attachment_id' => $data['data_destruction_certificate_attachment_id'] ?? $lockedDisposal->data_destruction_certificate_attachment_id,
                'disposal_certificate_attachment_id' => $data['disposal_certificate_attachment_id'] ?? $lockedDisposal->disposal_certificate_attachment_id,
                'remarks' => $data['remarks'] ?? $lockedDisposal->remarks,
            ]);

            $previousStatus = $lockedAsset->status;

            // Set asset status to DISPOSED (do not soft-delete, keeps historical integrity)
            $lockedAsset->update([
                'status' => AssetStatus::DISPOSED,
                'assigned_official_id' => null,
            ]);

            // Emit DISPOSED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::DISPOSED,
                $actor,
                [
                    'from_status' => $previousStatus,
                    'to_status' => AssetStatus::DISPOSED,
                    'reference_type' => 'AssetDisposal',
                    'reference_id' => $lockedDisposal->id,
                    'reference_number' => $lockedDisposal->disposal_number,
                    'remarks' => "Asset disposed via method {$lockedDisposal->disposal_method?->value} (Disposal #{$lockedDisposal->disposal_number})",
                    'metadata' => [
                        'disposal_number' => $lockedDisposal->disposal_number,
                        'disposal_method' => $lockedDisposal->disposal_method?->value,
                        'sale_value' => $lockedDisposal->sale_value,
                    ],
                ]
            );

            return $lockedDisposal->fresh();
        });
    }
}
