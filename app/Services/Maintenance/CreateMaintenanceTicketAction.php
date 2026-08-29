<?php

namespace App\Services\Maintenance;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Enums\MaintenanceSeverity;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateMaintenanceTicketAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Create a new maintenance ticket and update asset status if requested.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Asset $asset, array $data, User $actor): MaintenanceTicket
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            if ($lockedAsset->status === AssetStatus::DISPOSED) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Cannot create a maintenance ticket for a disposed asset.',
                ]);
            }

            $ticketNumber = $data['ticket_number'] ?? 'MNT-' . strtoupper(Str::random(8));

            $ticket = MaintenanceTicket::create([
                'asset_id' => $lockedAsset->id,
                'organizational_unit_id' => $lockedAsset->organizational_unit_id,
                'ticket_number' => $ticketNumber,
                'issue_category' => $data['issue_category'] ?? null,
                'issue_description' => $data['issue_description'],
                'reported_by_user_id' => $actor->id,
                'reported_by_official_id' => $data['reported_by_official_id'] ?? $lockedAsset->assigned_official_id,
                'reported_at' => $data['reported_at'] ?? now(),
                'status' => MaintenanceStatus::OPEN,
                'severity' => $data['severity'] ?? MaintenanceSeverity::MEDIUM,
                'agreement_id' => $data['agreement_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'covered_under_warranty' => (bool) ($data['covered_under_warranty'] ?? false),
                'covered_under_amc' => (bool) ($data['covered_under_amc'] ?? false),
                'remarks' => $data['remarks'] ?? null,
            ]);

            $previousStatus = $lockedAsset->status;

            // Transition asset to UNDER_MAINTENANCE if specified or default
            if (($data['set_asset_under_maintenance'] ?? true) && $lockedAsset->status !== AssetStatus::UNDER_MAINTENANCE) {
                $lockedAsset->update(['status' => AssetStatus::UNDER_MAINTENANCE]);
            }

            // Emit MAINTENANCE_OPENED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::MAINTENANCE_OPENED,
                $actor,
                [
                    'from_status' => $previousStatus,
                    'to_status' => $lockedAsset->status,
                    'reference_type' => 'MaintenanceTicket',
                    'reference_id' => $ticket->id,
                    'reference_number' => $ticket->ticket_number,
                    'remarks' => "Maintenance ticket #{$ticket->ticket_number} opened: {$ticket->issue_description}",
                    'metadata' => [
                        'ticket_number' => $ticket->ticket_number,
                        'issue_category' => $ticket->issue_category,
                        'severity' => $ticket->severity->value,
                    ],
                ]
            );

            return $ticket;
        });
    }
}
