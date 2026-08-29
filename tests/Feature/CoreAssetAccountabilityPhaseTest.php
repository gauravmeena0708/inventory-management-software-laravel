<?php

namespace Tests\Feature;

use App\Enums\AgreementCoverageType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\DisposalMethod;
use App\Enums\DisposalStatus;
use App\Enums\LifecycleEventType;
use App\Enums\MaintenanceSeverity;
use App\Enums\MaintenanceStatus;
use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposal;
use App\Models\AssetLifecycleEvent;
use App\Models\AssetTransfer;
use App\Models\Location;
use App\Models\MaintenanceTicket;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Agreements\AttachAssetToAgreementAction;
use App\Services\Agreements\DetachAssetFromAgreementAction;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\DecommissionAssetAction;
use App\Services\Assets\ReturnAssetAction;
use App\Services\Disposal\CompleteAssetDisposalAction;
use App\Services\Disposal\RecommendAssetDisposalAction;
use App\Services\Maintenance\CreateMaintenanceTicketAction;
use App\Services\Maintenance\ResolveMaintenanceTicketAction;
use App\Services\Organization\OrganizationalContext;
use App\Services\Transfers\DispatchAssetTransferAction;
use App\Services\Transfers\InitiateAssetTransferAction;
use App\Services\Transfers\ReceiveAssetTransferAction;
use App\Services\Transfers\RejectAssetTransferAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoreAssetAccountabilityPhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerUnitA;
    private User $managerUnitB;
    private OrganizationalUnit $unitA;
    private OrganizationalUnit $unitB;
    private Site $siteA;
    private Site $siteB;
    private Location $locationA;
    private Location $locationB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->unitA = OrganizationalUnit::create([
            'code' => 'RO-DEL',
            'name' => 'Regional Office Delhi',
            'unit_type' => \App\Enums\OrganizationalUnitType::ROOT,
            'is_active' => true,
        ]);

        $this->unitB = OrganizationalUnit::create([
            'code' => 'RO-JAI',
            'name' => 'Regional Office Jaipur',
            'unit_type' => \App\Enums\OrganizationalUnitType::ROOT,
            'is_active' => true,
        ]);

        $this->siteA = Site::create([
            'code' => 'SITE-DEL-HQ',
            'name' => 'Delhi Headquarters',
            'is_active' => true,
        ]);
        $this->siteA->organizationalUnits()->attach($this->unitA->id);

        $this->siteB = Site::create([
            'code' => 'SITE-JAI-HQ',
            'name' => 'Jaipur Headquarters',
            'is_active' => true,
        ]);
        $this->siteB->organizationalUnits()->attach($this->unitB->id);

        $this->locationA = Location::factory()->create([
            'site_id' => $this->siteA->id,
            'name' => 'Delhi Server Room',
            'location_type' => \App\Enums\LocationType::ROOM,
            'is_active' => true,
        ]);

        $this->locationB = Location::factory()->create([
            'site_id' => $this->siteB->id,
            'name' => 'Jaipur Asset Store',
            'location_type' => \App\Enums\LocationType::ROOM,
            'is_active' => true,
        ]);

        $this->managerUnitA = User::factory()->create([
            'role' => UserRole::INVENTORY_MANAGER,
        ]);
        $this->managerUnitA->organizationalUnits()->attach($this->unitA->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        $this->managerUnitB = User::factory()->create([
            'role' => UserRole::INVENTORY_MANAGER,
        ]);
        $this->managerUnitB->organizationalUnits()->attach($this->unitB->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        app(OrganizationalContext::class)->setDefaultUnit($this->managerUnitA, $this->unitA->id);
        app(OrganizationalContext::class)->setDefaultUnit($this->managerUnitB, $this->unitB->id);
    }

    public function test_asset_categories_hierarchy_and_asset_association(): void
    {
        $parent = AssetCategory::create([
            'code' => 'NET-ROOT',
            'name' => 'Networking Hardware',
            'broad_family' => 'NETWORK',
            'is_active' => true,
        ]);

        $child = AssetCategory::create([
            'code' => 'CORE-SWITCH',
            'name' => 'L3 Core Switch',
            'parent_id' => $parent->id,
            'broad_family' => 'NETWORK',
            'is_active' => true,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains('id', $child->id));

        $asset = Asset::create([
            'name' => 'Cisco Catalyst 9500',
            'asset_tag' => 'TAG-SW-01',
            'asset_type' => AssetType::SWITCH,
            'asset_category_id' => $child->id,
            'organizational_unit_id' => $this->unitA->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        $this->assertEquals('CORE-SWITCH', $asset->category->code);
        $this->assertEquals('Networking Hardware', $asset->category->parent->name);
    }

    public function test_unified_asset_lifecycle_event_stream_across_assignment_and_return(): void
    {
        $official = Official::factory()->create([
            'name' => 'Rajesh Sharma',
            'email' => 'rajesh.sharma@example.gov.in',
        ]);

        /** @var CreateAssetAction $createAction */
        $createAction = app(CreateAssetAction::class);
        $asset = $createAction->execute([
            'name' => 'Dell Latitude 7440',
            'asset_tag' => 'TAG-DEL-001',
            'asset_type' => AssetType::LAPTOP,
            'organizational_unit_id' => $this->unitA->id,
            'status' => AssetStatus::IN_STOCK,
        ], $this->managerUnitA);

        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::REGISTERED->value,
            'actor_user_id' => $this->managerUnitA->id,
        ]);

        /** @var AssignAssetAction $assignAction */
        $assignAction = app(AssignAssetAction::class);
        $assignment = $assignAction->execute(
            $asset,
            $official,
            $this->managerUnitA,
            'Issued for field work',
            'Brand new in box'
        );

        $this->assertEquals(AssetStatus::IN_USE, $asset->fresh()->status);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::ASSIGNED->value,
            'from_status' => AssetStatus::IN_STOCK->value,
            'to_status' => AssetStatus::IN_USE->value,
        ]);

        /** @var ReturnAssetAction $returnAction */
        $returnAction = app(ReturnAssetAction::class);
        $returnAction->execute(
            $asset,
            $this->managerUnitA,
            'Returned in good working condition',
            'Normal wear'
        );

        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::RETURNED->value,
            'from_status' => AssetStatus::IN_USE->value,
            'to_status' => AssetStatus::IN_STOCK->value,
        ]);

        $timeline = $asset->fresh()->lifecycleEvents;
        $this->assertCount(3, $timeline);
    }

    public function test_agreement_asset_coverage_mapping(): void
    {
        $agreement = Agreement::create([
            'name' => 'Dell Enterprise Server AMC 2026-2027',
            'agency' => 'Dell Global Services',
            'type' => 'AMC',
            'annual_cost' => 120000.00,
            'billing_interval_months' => 12,
            'billing_anchor_date' => '2026-04-01',
            'expiry' => '2027-03-31',
            'organizational_unit_id' => $this->unitA->id,
        ]);

        $asset = Asset::create([
            'name' => 'PowerEdge R750',
            'asset_tag' => 'TAG-SRV-01',
            'asset_type' => AssetType::SERVER,
            'organizational_unit_id' => $this->unitA->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var AttachAssetToAgreementAction $attachAction */
        $attachAction = app(AttachAssetToAgreementAction::class);
        $attachAction->execute($agreement, $asset, [
            'coverage_type' => AgreementCoverageType::COMPREHENSIVE_AMC,
            'coverage_start' => '2026-04-01',
            'coverage_end' => '2027-03-31',
            'sla_reference' => '4-Hour 24x7 Mission Critical Response',
            'remarks' => 'Covered under Delhi DC cluster AMC',
        ], $this->managerUnitA);

        $this->assertEquals(1, $agreement->assets()->count());
        $this->assertEquals(1, $asset->agreements()->count());
        $this->assertEquals('comprehensive_amc', $asset->agreements->first()->pivot->coverage_type);

        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::AGREEMENT_ATTACHED->value,
            'reference_type' => 'Agreement',
            'reference_id' => $agreement->id,
        ]);

        /** @var DetachAssetFromAgreementAction $detachAction */
        $detachAction = app(DetachAssetFromAgreementAction::class);
        $detachAction->execute($agreement, $asset, $this->managerUnitA);

        $this->assertEquals(0, $agreement->assets()->count());
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::AGREEMENT_REMOVED->value,
        ]);
    }

    public function test_maintenance_ticket_full_lifecycle(): void
    {
        $asset = Asset::create([
            'name' => 'HP LaserJet Pro M404',
            'asset_tag' => 'TAG-PRN-01',
            'asset_type' => AssetType::OTHER,
            'organizational_unit_id' => $this->unitA->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var CreateMaintenanceTicketAction $createTicketAction */
        $createTicketAction = app(CreateMaintenanceTicketAction::class);
        $ticket = $createTicketAction->execute($asset, [
            'issue_category' => 'Paper Feed Failure',
            'issue_description' => 'Fuser roller damaged causing repeated paper jams.',
            'severity' => MaintenanceSeverity::HIGH,
            'set_asset_under_maintenance' => true,
        ], $this->managerUnitA);

        $this->assertEquals(MaintenanceStatus::OPEN, $ticket->status);
        $this->assertEquals(AssetStatus::UNDER_MAINTENANCE, $asset->fresh()->status);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::MAINTENANCE_OPENED->value,
            'to_status' => AssetStatus::UNDER_MAINTENANCE->value,
        ]);

        /** @var ResolveMaintenanceTicketAction $resolveTicketAction */
        $resolveTicketAction = app(ResolveMaintenanceTicketAction::class);
        $resolvedTicket = $resolveTicketAction->execute($ticket, [
            'diagnosis' => 'Defective pickup roller and fuser film replaced.',
            'resolution' => 'Replaced parts under warranty, test printed 50 pages successfully.',
            'cost' => 0.00,
            'downtime_minutes' => 180,
            'status' => MaintenanceStatus::CLOSED,
        ], $this->managerUnitA);

        $this->assertEquals(MaintenanceStatus::CLOSED, $resolvedTicket->status);
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::MAINTENANCE_COMPLETED->value,
            'to_status' => AssetStatus::IN_STOCK->value,
        ]);
    }

    public function test_inter_unit_asset_transfer_workflow_locks_and_transfers_ownership(): void
    {
        $asset = Asset::create([
            'name' => 'MacBook Pro 16',
            'asset_tag' => 'TAG-MBP-99',
            'asset_type' => AssetType::LAPTOP,
            'organizational_unit_id' => $this->unitA->id,
            'location_id' => $this->locationA->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var InitiateAssetTransferAction $initiateAction */
        $initiateAction = app(InitiateAssetTransferAction::class);
        $transfer = $initiateAction->execute($asset, [
            'to_organizational_unit_id' => $this->unitB->id,
            'to_location_id' => $this->locationB->id,
            'remarks' => 'Permanent transfer for Jaipur IT setup.',
        ], $this->managerUnitA);

        $this->assertEquals(TransferStatus::PENDING_APPROVAL, $transfer->status);

        /** @var DispatchAssetTransferAction $dispatchAction */
        $dispatchAction = app(DispatchAssetTransferAction::class);
        $dispatchedTransfer = $dispatchAction->execute($transfer, [
            'condition_at_dispatch' => 'Packed in secure courier crate',
        ], $this->managerUnitA);

        $this->assertEquals(TransferStatus::IN_TRANSIT, $dispatchedTransfer->status);
        $this->assertEquals(AssetStatus::IN_TRANSIT, $asset->fresh()->status);

        /** @var ReceiveAssetTransferAction $receiveAction */
        $receiveAction = app(ReceiveAssetTransferAction::class);
        $receivedTransfer = $receiveAction->execute($dispatchedTransfer, [
            'condition_at_receipt' => 'Received intact and verified serial number',
            'to_location_id' => $this->locationB->id,
        ], $this->managerUnitB);

        $this->assertEquals(TransferStatus::RECEIVED, $receivedTransfer->status);

        // Verify asset ownership and location have shifted to Unit B / Location B
        $updatedAsset = $asset->fresh();
        $this->assertEquals($this->unitB->id, $updatedAsset->organizational_unit_id);
        $this->assertEquals($this->locationB->id, $updatedAsset->location_id);
        $this->assertEquals(AssetStatus::IN_STOCK, $updatedAsset->status);

        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::TRANSFER_RECEIVED->value,
            'from_organizational_unit_id' => $this->unitA->id,
            'to_organizational_unit_id' => $this->unitB->id,
            'to_status' => AssetStatus::IN_STOCK->value,
        ]);
    }

    public function test_condemnation_and_disposal_workflow_preserves_historical_records(): void
    {
        $asset = Asset::create([
            'name' => 'Legacy Pentium IV Server',
            'asset_tag' => 'TAG-OBSOLETE-01',
            'asset_type' => AssetType::SERVER,
            'organizational_unit_id' => $this->unitA->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var RecommendAssetDisposalAction $recommendAction */
        $recommendAction = app(RecommendAssetDisposalAction::class);
        $disposal = $recommendAction->execute($asset, [
            'committee_reference' => 'CONDEMN-COMM-2026/04',
            'inspection_reference' => 'TECH-REPORT-994',
            'disposal_method' => DisposalMethod::E_WASTE,
            'data_destruction_required' => true,
            'remarks' => 'Beyond economical repair and obsolete architecture.',
        ], $this->managerUnitA);

        $this->assertEquals(DisposalStatus::RECOMMENDED, $disposal->status);
        $this->assertEquals(AssetStatus::PENDING_DISPOSAL, $asset->fresh()->status);

        /** @var CompleteAssetDisposalAction $completeAction */
        $completeAction = app(CompleteAssetDisposalAction::class);
        $completedDisposal = $completeAction->execute($disposal, [
            'disposal_vendor' => 'Authorized E-Waste Recycler Pvt Ltd',
            'approval_reference' => 'FIN-SANCTION-2026/88',
            'sale_value' => 500.00,
            'remarks' => 'Scrapped and certified data degaussing completed.',
        ], $this->managerUnitA);

        $this->assertEquals(DisposalStatus::DISPOSED, $completedDisposal->status);

        // Disposed assets are NOT soft-deleted: they remain permanently visible with status DISPOSED
        $freshAsset = $asset->fresh();
        $this->assertNotNull($freshAsset);
        $this->assertEquals(AssetStatus::DISPOSED, $freshAsset->status);
        $this->assertNull($freshAsset->deleted_at);

        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::DISPOSED->value,
            'to_status' => AssetStatus::DISPOSED->value,
        ]);
    }
}
