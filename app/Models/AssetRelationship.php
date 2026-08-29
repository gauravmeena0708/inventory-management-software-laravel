<?php

namespace App\Models;

use App\Enums\AssetRelationshipType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_asset_id',
        'child_asset_id',
        'relationship_type',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => AssetRelationshipType::class,
        ];
    }

    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_asset_id');
    }

    public function childAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'child_asset_id');
    }
}
