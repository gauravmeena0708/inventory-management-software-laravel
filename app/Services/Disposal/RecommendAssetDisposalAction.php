<?php

namespace App\Services\Disposal;

use App\Enums\AssetStatus;
use App\Enums\DisposalMethod;
use App\Enums\DisposalStatus;
use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecommendAssetDisposalAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Recommend an asset for condemnation and disposal.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Asset $asset, array $data, User $actor): AssetDisposal
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            if ($lockedAsset->status === AssetStatus::DISPOSED) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Asset is already disposed.',
                ]);
            }

            $disposalNumber = $data['disposal_number'] ?? 'DSP-' . strtoupper(Str::random(8));

            $disposal = AssetDisposal::create([
                'asset_id' => $lockedAsset->id,
                'organizational_unit_id' => $lockedAsset->organizational_unit_id,
                'disposal_number' => $disposalNumber,
                'status' => DisposalStatus::RECOMMENDED,
                'recommendation_date' => $data['recommendation_date'] ?? now()->toDateString(),
                'recommended_by' => $actor->id,
                'committee_reference' => $data['committee_reference'] ?? null,
                'inspection_reference' => $data['inspection_reference'] ?? null,
                'disposal_method' => $data['disposal_method'] ?? DisposalMethod::E_WASTE,
                'data_destruction_required' => (bool) ($data['data_destruction_required'] ?? false),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $previousStatus = $lockedAsset->status;
            $lockedAsset->update(['status' => AssetStatus::PENDING_DISPOSAL]);

            // Emit CONDEMNATION_RECOMMENDED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::CONDEMNATION_RECOMMENDED,
                $actor,
                [
                    'from_status' => $previousStatus,
                    'to_status' => AssetStatus::PENDING_DISPOSAL,
                    'reference_type' => 'AssetDisposal',
                    'reference_id' => $disposal->id,
                    'reference_number' => $disposal->disposal_number,
                    'remarks' => "Condemnation recommended: {$disposal->remarks}",
                    'metadata' => [
                        'disposal_number' => $disposal->disposal_number,
                        'disposal_method' => $disposal->disposal_method?->value,
                        'committee_reference' => $disposal->committee_reference,
                    ],
                ]
            );

            return $disposal;
        });
    }
}
