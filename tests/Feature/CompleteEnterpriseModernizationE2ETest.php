<?php

namespace Tests\Feature;

use App\Enums\AcquisitionType;
use App\Enums\AssetRelationshipType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\AuditResponseType;
use App\Enums\AuditStatus;
use App\Enums\AuditType;
use App\Enums\DisposalMethod;
use App\Enums\DisposalStatus;
use App\Enums\LifecycleEventType;
use App\Enums\LocationType;
use App\Enums\MaintenanceSeverity;
use App\Enums\MaintenanceStatus;
use App\Enums\ObservationSeverity;
use App\Enums\ObservationStatus;
use App\Enums\OrganizationalUnitType;
use App\Enums\ReportRunStatus;
use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Enums\VerificationResult;
use App\Enums\VerificationStatus;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\Acquisition\CreateAcquisitionAction;
use App\Services\Acquisition\LinkAssetToAcquisitionAction;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\LinkAssetRelationshipAction;
use App\Services\Assets\ReturnAssetAction;
use App\Services\Audit\AtrReportGenerator;
use App\Services\Audit\CreateAuditEngagementAction;
use App\Services\Audit\IssueAuditObservationAction;
use App\Services\Audit\SettleAuditObservationAction;
use App\Services\Audit\SubmitAuditResponseAction;
use App\Services\DataQuality\InventoryDataQualityService;
use App\Services\Disposal\CompleteAssetDisposalAction;
use App\Services\Disposal\RecommendAssetDisposalAction;
use App\Services\Maintenance\CreateMaintenanceTicketAction;
use App\Services\Maintenance\ResolveMaintenanceTicketAction;
use App\Services\Organization\OrganizationalContext;
use App\Services\Qr\QrTagService;
use App\Services\Reporting\CertifyReportRunAction;
use App\Services\Reporting\GenerateReportRunAction;
use App\Services\Transfers\DispatchAssetTransferAction;
use App\Services\Transfers\InitiateAssetTransferAction;
use App\Services\Transfers\ReceiveAssetTransferAction;
use App\Services\Verification\CertifyVerificationCampaignAction;
use App\Services\Verification\CreateVerificationCampaignAction;
use App\Services\Verification\ReconcileVerificationDiscrepancyAction;
use App\Services\Verification\RecordVerificationScanAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteEnterpriseModernizationE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_enterprise_inventory_modernization_lifecycle(): void
    {
        // 1. Setup Organizational Hierarchy
        $hqUnit = OrganizationalUnit::create([
            'code' => 'HQ-DELHI',
            'name' => 'Headquarters New Delhi',
            'unit_type' => OrganizationalUnitType::ROOT,
            'path' => '1',
            'is_active' => true,
        ]);

        $regionalUnit = OrganizationalUnit::create([
            'code' => 'RO-MUMBAI',
            'name' => 'Regional Office Mumbai',
            'unit_type' => OrganizationalUnitType::REGIONAL_OFFICE,
            'parent_id' => $hqUnit->id,
            'path' => '1/2',
            'is_active' => true,
        ]);

        $hqSite = Site::create(['code' => 'SITE-HQ', 'name' => 'HQ IT Complex', 'is_active' => true]);
        $hqSite->organizationalUnits()->attach($hqUnit->id);
        $hqRoom = Location::factory()->create(['site_id' => $hqSite->id, 'name' => 'HQ Server Room']);

        $roSite = Site::create(['code' => 'SITE-RO', 'name' => 'Mumbai Office Complex', 'is_active' => true]);
        $roSite->organizationalUnits()->attach($regionalUnit->id);
        $roRoom = Location::factory()->create(['site_id' => $roSite->id, 'name' => 'Mumbai IT Room']);

        $managerHQ = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $managerHQ->organizationalUnits()->attach($hqUnit->id, ['read_scope' => 'descendants', 'write_scope' => 'local']);

        $managerRO = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $managerRO->organizationalUnits()->attach($regionalUnit->id, ['read_scope' => 'local', 'write_scope' => 'local']);

        $auditor = User::factory()->create(['role' => UserRole::AUDITOR]);
        $auditor->organizationalUnits()->attach($hqUnit->id, ['read_scope' => 'descendants', 'write_scope' => 'descendants']);

        app(OrganizationalContext::class)->setDefaultUnit($managerHQ, $hqUnit->id);
        app(OrganizationalContext::class)->setDefaultUnit($managerRO, $regionalUnit->id);
        app(OrganizationalContext::class)->setDefaultUnit($auditor, $hqUnit->id);

        $category = AssetCategory::firstOrCreate(['code' => 'SERVER_CORE'], [
            'name' => 'Core Compute & Servers',
            'broad_family' => 'COMPUTE',
            'is_active' => true,
        ]);

        // 2. Asset Registration & GeM Acquisition Provenance
        /** @var CreateAssetAction $createAsset */
        $createAsset = app(CreateAssetAction::class);
        $asset = $createAsset->execute([
            'name' => 'Enterprise Cluster Node 01',
            'asset_tag' => 'AST-ENT-001',
            'serial_number' => 'SRV-SN-998811',
            'asset_category_id' => $category->id,
            'asset_type' => AssetType::SERVER,
            'purchase_cost' => 350000.00,
            'purchase_date' => '2026-02-01',
            'warranty_expiry' => '2029-02-01',
            'location_id' => $hqRoom->id,
            'organizational_unit_id' => $hqUnit->id,
        ], $managerHQ);

        $this->assertEquals(AssetStatus::IN_STOCK, $asset->status);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::REGISTERED->value,
        ]);

        /** @var CreateAcquisitionAction $createAcquisition */
        $createAcquisition = app(CreateAcquisitionAction::class);
        $acquisition = $createAcquisition->execute([
            'organizational_unit_id' => $hqUnit->id,
            'acquisition_type' => AcquisitionType::GEM,
            'gem_order_number' => 'GEMC-511687799001',
            'total_value' => 350000.00,
        ], $managerHQ);

        app(LinkAssetToAcquisitionAction::class)->execute($acquisition, $asset, ['unit_cost' => 350000.00], $managerHQ);
        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::ACQUIRED->value,
        ]);

        // 3. QR Tag generation
        $qrLabel = app(QrTagService::class)->getLabelData($asset);
        $this->assertStringContainsString('AST-ENT-001', $qrLabel['asset_tag']);
        $this->assertStringContainsString('<svg', $qrLabel['qr_svg']);

        // 4. Custodian Assignment and Return
        $official = Official::factory()->create(['name' => 'Sunil Kumar']);
        app(AssignAssetAction::class)->execute($asset, $official, $managerHQ, 'Assigned for cluster management');
        $this->assertEquals(AssetStatus::IN_USE, $asset->fresh()->status);
        $this->assertEquals($official->id, $asset->fresh()->assigned_official_id);

        app(ReturnAssetAction::class)->execute($asset, $managerHQ, 'Returned to IT stock');
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertNull($asset->fresh()->assigned_official_id);

        // 5. Maintenance Ticket Cycle
        $ticket = app(CreateMaintenanceTicketAction::class)->execute($asset, [
            'ticket_type' => 'repair',
            'severity' => MaintenanceSeverity::MEDIUM,
            'issue_description' => 'Cooling fan warning reported on chassis',
        ], $managerHQ);

        $this->assertEquals(AssetStatus::UNDER_MAINTENANCE, $asset->fresh()->status);
        $this->assertEquals(MaintenanceStatus::OPEN, $ticket->status);

        app(ResolveMaintenanceTicketAction::class)->execute($ticket, [
            'resolution_notes' => 'Fan module replaced under warranty.',
            'repair_cost' => 0.00,
        ], $managerHQ);

        $this->assertEquals(MaintenanceStatus::RESOLVED, $ticket->fresh()->status);
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);

        // 6. Inter-Office Asset Transfer (HQ -> RO Mumbai)
        $transfer = app(InitiateAssetTransferAction::class)->execute($asset, [
            'to_organizational_unit_id' => $regionalUnit->id,
            'to_location_id' => $roRoom->id,
            'reason' => 'Permanent redeployment to Mumbai DR node',
        ], $managerHQ);

        app(DispatchAssetTransferAction::class)->execute($transfer, ['carrier_name' => 'SpeedPost Cargo'], $managerHQ);
        $this->assertEquals(AssetStatus::IN_TRANSIT, $asset->fresh()->status);
        $this->assertEquals(TransferStatus::IN_TRANSIT, $transfer->fresh()->status);

        app(ReceiveAssetTransferAction::class)->execute($transfer, ['remarks' => 'Received node in good condition at Mumbai'], $managerRO);
        $this->assertEquals(TransferStatus::RECEIVED, $transfer->fresh()->status);
        $this->assertEquals($regionalUnit->id, $asset->fresh()->organizational_unit_id);
        $this->assertEquals($roRoom->id, $asset->fresh()->location_id);
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);

        // 7. Physical Verification Campaign
        $campaign = app(CreateVerificationCampaignAction::class)->execute([
            'organizational_unit_id' => $regionalUnit->id,
            'name' => 'Mumbai Annual Verification 2026',
        ], $managerRO);

        $item = app(RecordVerificationScanAction::class)->execute($campaign, $asset->fresh(), [
            'observed_location_id' => $roRoom->id,
        ], $managerRO);

        $this->assertEquals(VerificationResult::VERIFIED, $item->result);

        $certifiedCampaign = app(CertifyVerificationCampaignAction::class)->execute($campaign, [], $managerRO);
        $this->assertEquals(VerificationStatus::CERTIFIED, $certifiedCampaign->status);

        // 8. Data Quality & Completeness
        $dqEval = app(InventoryDataQualityService::class)->evaluateAsset($asset->fresh());
        $this->assertGreaterThanOrEqual(80, $dqEval['score']);

        // 9. Certified Register Generation & SHA-256
        $reportDef = ReportDefinition::where('code', 'INV-ASSET-REGISTER')->firstOrFail();
        $reportRun = app(GenerateReportRunAction::class)->execute($reportDef, $regionalUnit, [], $managerRO, 'local');
        $certifiedRun = app(CertifyReportRunAction::class)->execute($reportRun, [], $managerRO);
        $this->assertEquals(ReportRunStatus::FINAL, $certifiedRun->status);
        $this->assertNotEmpty($certifiedRun->sha256);

        // 10. Audit Engagement, Observation, Reply & Settlement
        $audit = app(CreateAuditEngagementAction::class)->execute([
            'audit_type' => AuditType::INVENTORY_AUDIT,
            'audited_organizational_unit_id' => $regionalUnit->id,
            'auditing_organizational_unit_id' => $hqUnit->id,
        ], $auditor);

        $obs = app(IssueAuditObservationAction::class)->execute($audit, [
            'para_number' => 'Para 1.1',
            'title' => 'Verification Compliance Check',
            'finding' => 'Verify transfer ledger completeness for transferred server AST-ENT-001.',
            'severity' => ObservationSeverity::MEDIUM,
            'asset_ids' => [$asset->id],
        ], $auditor);

        app(SubmitAuditResponseAction::class)->execute($obs, [
            'body' => 'Transfer #'.$transfer->transfer_number.' receipt verified and signed off.',
        ], $managerRO);

        app(SettleAuditObservationAction::class)->execute($obs, ['remarks' => 'Verification verified. Para settled.'], $auditor);
        $this->assertEquals(ObservationStatus::SETTLED, $obs->fresh()->status);
        $this->assertEquals(AuditStatus::CLOSED, $audit->fresh()->status);

        $atr = app(AtrReportGenerator::class)->generate($audit);
        $this->assertEquals(100, $atr['summary']['compliance_percentage']);

        // 11. Condemnation & Permanent Disposal Record
        $disposal = app(RecommendAssetDisposalAction::class)->execute($asset->fresh(), [
            'disposal_method' => DisposalMethod::E_WASTE,
            'condemnation_reason' => 'Chassis reaching end of operational life after 5 years',
            'estimated_scrap_value' => 5000.00,
        ], $managerRO);

        $this->assertEquals(AssetStatus::PENDING_DISPOSAL, $asset->fresh()->status);

        app(CompleteAssetDisposalAction::class)->execute($disposal, [
            'actual_recovery_amount' => 5200.00,
            'data_destruction_certified' => true,
            'remarks' => 'Certified destruction of media and e-waste recycling.',
        ], $managerRO);

        $this->assertEquals(AssetStatus::DISPOSED, $asset->fresh()->status);
        $this->assertEquals(DisposalStatus::DISPOSED, $disposal->fresh()->status);

        // Disposed record remains permanently retained
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => AssetStatus::DISPOSED->value,
            'deleted_at' => null,
        ]);
    }
}
