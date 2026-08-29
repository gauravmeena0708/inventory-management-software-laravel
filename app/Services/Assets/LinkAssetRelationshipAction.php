<?php

namespace App\Services\Assets;

use App\Enums\AssetRelationshipType;
use App\Models\Asset;
use App\Models\AssetRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkAssetRelationshipAction
{
    /**
     * Establish a parent-child relationship between two assets.
     */
    public function execute(
        Asset $parentAsset,
        Asset $childAsset,
        AssetRelationshipType|string $relationshipType = AssetRelationshipType::COMPONENT_OF,
        ?string $remarks = null
    ): AssetRelationship {
        return DB::transaction(function () use ($parentAsset, $childAsset, $relationshipType, $remarks) {
            if ($parentAsset->id === $childAsset->id) {
                throw ValidationException::withMessages([
                    'child_asset_id' => 'An asset cannot have a relationship with itself.',
                ]);
            }

            $type = $relationshipType instanceof AssetRelationshipType ? $relationshipType : AssetRelationshipType::from($relationshipType);

            return AssetRelationship::updateOrCreate([
                'parent_asset_id' => $parentAsset->id,
                'child_asset_id' => $childAsset->id,
                'relationship_type' => $type->value,
            ], [
                'remarks' => $remarks,
            ]);
        });
    }
}
