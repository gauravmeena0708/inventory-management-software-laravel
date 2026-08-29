<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetLifecycleEvent;
use App\Models\User;
use DateTimeInterface;

class RecordLifecycleEventAction
{
    /**
     * Record a lifecycle event for an asset in the authoritative event stream.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(
        Asset $asset,
        LifecycleEventType|string $eventType,
        ?User $actor = null,
        array $options = []
    ): AssetLifecycleEvent {
        $type = $eventType instanceof LifecycleEventType ? $eventType : LifecycleEventType::from($eventType);

        $occurredAt = $options['occurred_at'] ?? now();
        if (is_string($occurredAt)) {
            $occurredAt = now()->parse($occurredAt);
        }

        $fromStatus = isset($options['from_status'])
            ? ($options['from_status'] instanceof AssetStatus ? $options['from_status']->value : $options['from_status'])
            : null;

        $toStatus = isset($options['to_status'])
            ? ($options['to_status'] instanceof AssetStatus ? $options['to_status']->value : $options['to_status'])
            : ($asset->status instanceof AssetStatus ? $asset->status->value : $asset->status);

        return AssetLifecycleEvent::create([
            'asset_id' => $asset->id,
            'event_type' => $type->value,
            'occurred_at' => $occurredAt,
            'actor_user_id' => $actor?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_organizational_unit_id' => $options['from_organizational_unit_id'] ?? null,
            'to_organizational_unit_id' => $options['to_organizational_unit_id'] ?? $asset->organizational_unit_id,
            'from_location_id' => $options['from_location_id'] ?? null,
            'to_location_id' => $options['to_location_id'] ?? $asset->location_id,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'reference_number' => $options['reference_number'] ?? null,
            'remarks' => $options['remarks'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }
}
