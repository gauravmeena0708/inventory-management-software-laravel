<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateAssetAction
{
    /**
     * Update an existing asset record.
     *
     * @param array<string, mixed> $data
     */
    public function execute(Asset $asset, array $data, ?User $actor = null): Asset
    {
        return DB::transaction(function () use ($asset, $data) {
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();
            $lockedAsset->update($data);

            return $lockedAsset->fresh();
        });
    }
}
