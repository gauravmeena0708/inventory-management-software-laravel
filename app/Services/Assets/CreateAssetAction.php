<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAssetAction
{
    /**
     * Create a new asset record.
     *
     * @param array<string, mixed> $data
     */
    public function execute(array $data, ?User $actor = null): Asset
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['status'])) {
                $data['status'] = AssetStatus::IN_STOCK;
            }

            return Asset::create($data);
        });
    }
}
