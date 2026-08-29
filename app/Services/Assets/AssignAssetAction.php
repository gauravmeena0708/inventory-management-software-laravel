<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Official;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class AssignAssetAction
{
    public function __construct(
        private readonly ?RecordLifecycleEventAction $recordLifecycleEvent = null
    ) {}

    /**
     * Assign an asset to an official, closing any previous open assignment.
     */
    public function execute(
        Asset $asset,
        Official $official,
        ?User $actor = null,
        ?string $remarks = null,
        ?string $conditionOut = null,
        DateTimeInterface|string|null $assignedAt = null,
        string $source = 'application'
    ): AssetAssignment {
        return DB::transaction(function () use (
            $asset,
            $official,
            $actor,
            $remarks,
            $conditionOut,
            $assignedAt,
            $source
        ) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            $timestamp = $assignedAt
                ? ($assignedAt instanceof DateTimeInterface ? $assignedAt : now()->parse($assignedAt))
                : now();

            $previousStatus = $lockedAsset->status;

            // Close any existing open assignment
            $openAssignments = AssetAssignment::where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->get();

            foreach ($openAssignments as $openAssignment) {
                $openAssignment->update([
                    'returned_at' => $timestamp,
                    'return_recorded_by' => $actor?->id,
                    'remarks' => trim(($openAssignment->remarks ? $openAssignment->remarks . "\n" : '') . '[Auto-closed on re-assignment]'),
                ]);
            }

            // Create new assignment
            /** @var AssetAssignment $assignment */
            $assignment = AssetAssignment::create([
                'asset_id' => $lockedAsset->id,
                'official_id' => $official->id,
                'assigned_by' => $actor?->id,
                'assigned_at' => $timestamp,
                'returned_at' => null,
                'return_recorded_by' => null,
                'source' => $source,
                'condition_out' => $conditionOut,
                'remarks' => $remarks,
            ]);

            // Update asset status and assigned official
            $lockedAsset->update([
                'assigned_official_id' => $official->id,
                'status' => AssetStatus::IN_USE,
            ]);

            // Record ASSIGNED lifecycle event
            $lifecycleAction = $this->recordLifecycleEvent ?? app(RecordLifecycleEventAction::class);
            $lifecycleAction->execute(
                $lockedAsset,
                LifecycleEventType::ASSIGNED,
                $actor,
                [
                    'occurred_at' => $timestamp,
                    'from_status' => $previousStatus,
                    'to_status' => AssetStatus::IN_USE,
                    'reference_type' => 'AssetAssignment',
                    'reference_id' => $assignment->id,
                    'remarks' => $remarks ?: "Assigned to official: {$official->name}",
                    'metadata' => [
                        'official_id' => $official->id,
                        'official_name' => $official->name,
                        'condition_out' => $conditionOut,
                    ],
                ]
            );

            return $assignment;
        });
    }
}
