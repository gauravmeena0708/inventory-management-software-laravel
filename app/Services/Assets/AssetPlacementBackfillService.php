<?php

namespace App\Services\Assets;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class AssetPlacementBackfillService
{
    /**
     * Backfill a current placement for assets that have a current location but no open placement.
     */
    public function backfill(): int
    {
        return DB::transaction(function (): int {
            $created = 0;

            Asset::query()
                ->whereNotNull('location_id')
                ->orderBy('id')
                ->chunkById(500, function ($assets) use (&$created): void {
                    foreach ($assets as $asset) {
                        $lockedAsset = Asset::query()
                            ->lockForUpdate()
                            ->findOrFail($asset->id);

                        if ($lockedAsset->placements()->open()->exists()) {
                            continue;
                        }

                        $lockedAsset->placements()->create([
                            'location_id' => $lockedAsset->location_id,
                            'placed_at' => $lockedAsset->updated_at ?? $lockedAsset->created_at ?? now(),
                            'removed_at' => null,
                            'open_marker' => true,
                            'placed_by' => null,
                            'source' => 'repair_backfill',
                            'remarks' => 'Current placement backfilled from assets.location_id.',
                        ]);

                        $created++;
                    }
                });

            return $created;
        }, 3);
    }
}
