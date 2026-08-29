<?php

namespace App\Services\Maintenance;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class ResolveMaintenanceTicketAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Resolve and optionally close a maintenance ticket, restoring asset status.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(MaintenanceTicket $ticket, array $data, User $actor): MaintenanceTicket
    {
        return DB::transaction(function () use ($ticket, $data, $actor) {
            /** @var MaintenanceTicket $lockedTicket */
            $lockedTicket = MaintenanceTicket::where('id', $ticket->id)->lockForUpdate()->firstOrFail();
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $lockedTicket->asset_id)->lockForUpdate()->firstOrFail();

            $completedAt = $data['completed_at'] ?? now();
            $status = isset($data['status'])
                ? ($data['status'] instanceof MaintenanceStatus ? $data['status'] : MaintenanceStatus::from($data['status']))
                : MaintenanceStatus::RESOLVED;

            $lockedTicket->update([
                'status' => $status,
                'diagnosis' => $data['diagnosis'] ?? $lockedTicket->diagnosis,
                'resolution' => $data['resolution'] ?? $lockedTicket->resolution,
                'cost' => $data['cost'] ?? $lockedTicket->cost,
                'currency' => $data['currency'] ?? $lockedTicket->currency,
                'completed_at' => $completedAt,
                'returned_at' => $data['returned_at'] ?? $lockedTicket->returned_at ?? now(),
                'downtime_minutes' => $data['downtime_minutes'] ?? $lockedTicket->downtime_minutes,
                'remarks' => $data['remarks'] ?? $lockedTicket->remarks,
            ]);

            $previousStatus = $lockedAsset->status;

            // If asset was UNDER_MAINTENANCE, restore to IN_USE (if assigned) or IN_STOCK
            if ($lockedAsset->status === AssetStatus::UNDER_MAINTENANCE) {
                $newStatus = $lockedAsset->assigned_official_id ? AssetStatus::IN_USE : AssetStatus::IN_STOCK;
                $lockedAsset->update(['status' => $newStatus]);
            }

            // Emit MAINTENANCE_COMPLETED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::MAINTENANCE_COMPLETED,
                $actor,
                [
                    'from_status' => $previousStatus,
                    'to_status' => $lockedAsset->status,
                    'reference_type' => 'MaintenanceTicket',
                    'reference_id' => $lockedTicket->id,
                    'reference_number' => $lockedTicket->ticket_number,
                    'remarks' => "Maintenance ticket #{$lockedTicket->ticket_number} resolved: " . ($lockedTicket->resolution ?: 'Completed'),
                    'metadata' => [
                        'ticket_number' => $lockedTicket->ticket_number,
                        'cost' => $lockedTicket->cost,
                        'resolution' => $lockedTicket->resolution,
                    ],
                ]
            );

            return $lockedTicket->fresh();
        });
    }
}
