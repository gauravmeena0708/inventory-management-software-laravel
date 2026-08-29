<?php

namespace App\Services\Agreements;

use App\Enums\LifecycleEventType;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class DetachAssetFromAgreementAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Remove asset coverage from an agreement.
     */
    public function execute(Agreement $agreement, Asset $asset, ?User $actor = null): void
    {
        DB::transaction(function () use ($agreement, $asset, $actor) {
            $agreement->assets()->detach($asset->id);

            // Emit AGREEMENT_REMOVED lifecycle event
            $this->recordLifecycleEvent->execute(
                $asset,
                LifecycleEventType::AGREEMENT_REMOVED,
                $actor,
                [
                    'reference_type' => 'Agreement',
                    'reference_id' => $agreement->id,
                    'reference_number' => $agreement->name,
                    'remarks' => "Removed from agreement coverage: {$agreement->name}",
                ]
            );
        });
    }
}
