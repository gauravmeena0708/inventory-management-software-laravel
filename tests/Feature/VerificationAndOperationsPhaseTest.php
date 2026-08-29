<?php

namespace Tests\Feature;

use App\Enums\AcquisitionType;
use App\Enums\AssetRelationshipType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\InventoryAlertType;
use App\Enums\LifecycleEventType;
use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Enums\VerificationResult;
use App\Enums\VerificationStatus;
use App\Models\Acquisition;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetTransfer;
use App\Models\InventoryAlert;
use App\Models\InventoryVerification;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Acquisition\CreateAcquisitionAction;
use App\Services\Acquisition\LinkAssetToAcquisitionAction;
use App\Services\Alerts\InventoryAlertScannerService;
use App\Services\Assets\LinkAssetRelationshipAction;
use App\Services\Bulk\BulkAssetImportService;
use App\Services\DataQuality\InventoryDataQualityService;
use App\Services\Organization\OrganizationalContext;
use App\Services\Qr\QrTagService;
use App\Services\Transfers\DispatchAssetTransferAction;
use App\Services\Transfers\InitiateAssetTransferAction;
use App\Services\Verification\CertifyVerificationCampaignAction;
use App\Services\Verification\CreateVerificationCampaignAction;
use App\Services\Verification\ReconcileVerificationDiscrepancyAction;
use App\Services\Verification\RecordVerificationScanAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationAndOperationsPhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private OrganizationalUnit $unit;
    private Site $site;
    private Location $locationRoom1;
    private Location $locationRoom2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->unit = OrganizationalUnit::create([
            'code' => 'RO-CHANDI',
            'name' => 'Regional Office Chandigarh',
            'unit_type' => OrganizationalUnitType::ROOT,
            'is_active' => true,
        ]);

        $this->site = Site::create([
            'code' => 'SITE-CHD-01',
            'name' => 'Chandigarh IT Complex',
            'is_active' => true,
        ]);
        $this->site->organizationalUnits()->attach($this->unit->id);

        $this->locationRoom1 = Location::factory()->create([
            'site_id' => $this->site->id,
            'name' => 'Server Room A',
            'location_type' => LocationType::ROOM,
            'is_active' => true,
        ]);

        $this->locationRoom2 = Location::factory()->create([
            'site_id' => $this->site->id,
            'name' => 'IT Helpdesk Store',
            'location_type' => LocationType::ROOM,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $this->manager->organizationalUnits()->attach($this->unit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        app(OrganizationalContext::class)->setDefaultUnit($this->manager, $this->unit->id);
    }

    public function test_physical_inventory_verification_campaign_scan_and_certification(): void
    {
        $asset1 = Asset::create([
            'name' => 'Dell PowerEdge R650',
            'asset_tag' => 'TAG-SRV-101',
            'asset_type' => AssetType::SERVER,
            'organizational_unit_id' => $this->unit->id,
            'location_id' => $this->locationRoom1->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        $asset2 = Asset::create([
            'name' => 'Cisco Catalyst 9300',
            'asset_tag' => 'TAG-SW-202',
            'asset_type' => AssetType::SWITCH,
            'organizational_unit_id' => $this->unit->id,
            'location_id' => $this->locationRoom1->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var CreateVerificationCampaignAction $createCampaign */
        $createCampaign = app(CreateVerificationCampaignAction::class);
        $campaign = $createCampaign->execute([
            'organizational_unit_id' => $this->unit->id,
            'name' => 'Annual IT Asset Audit 2026-27',
            'financial_year' => '2026-2027',
        ], $this->manager);

        $this->assertEquals(VerificationStatus::IN_PROGRESS, $campaign->status);
        $this->assertEquals(2, $campaign->snapshot_population['asset_count']);

        /** @var RecordVerificationScanAction $recordScan */
        $recordScan = app(RecordVerificationScanAction::class);

        // Scan asset 1: correct location -> VERIFIED
        $item1 = $recordScan->execute($campaign, $asset1, [
            'observed_location_id' => $this->locationRoom1->id,
        ], $this->manager);

        $this->assertEquals(VerificationResult::VERIFIED, $item1->result);
        $this->assertTrue($item1->is_reconciled);

        // Scan asset 2: found in Room 2 instead of Room 1 -> WRONG_LOCATION discrepancy
        $item2 = $recordScan->execute($campaign, $asset2, [
            'observed_location_id' => $this->locationRoom2->id,
            'remarks' => 'Found in IT Helpdesk Store instead of Server Room A',
        ], $this->manager);

        $this->assertEquals(VerificationResult::WRONG_LOCATION, $item2->result);
        $this->assertFalse($item2->is_reconciled);

        // Reconcile discrepancy
        /** @var ReconcileVerificationDiscrepancyAction $reconcileAction */
        $reconcileAction = app(ReconcileVerificationDiscrepancyAction::class);
        $reconciledItem = $reconcileAction->execute($item2, [
            'apply_observed_location' => true,
            'remarks' => 'Relocated officially in system to Room 2.',
        ], $this->manager);

        $this->assertTrue($reconciledItem->is_reconciled);
        $this->assertEquals($this->locationRoom2->id, $asset2->fresh()->location_id);

        // Certify campaign
        /** @var CertifyVerificationCampaignAction $certifyAction */
        $certifyAction = app(CertifyVerificationCampaignAction::class);
        $certifiedCampaign = $certifyAction->execute($campaign, ['remarks' => 'All assets accounted for.'], $this->manager);

        $this->assertEquals(VerificationStatus::CERTIFIED, $certifiedCampaign->status);
        $this->assertNotNull($certifiedCampaign->certified_at);
    }

    public function test_qr_tag_service_label_generation(): void
    {
        $asset = Asset::create([
            'name' => 'HP EliteBook 840 G10',
            'asset_tag' => 'TAG-NB-5501',
            'serial_number' => '5CG3490ABC',
            'asset_type' => AssetType::LAPTOP,
            'organizational_unit_id' => $this->unit->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var QrTagService $qrService */
        $qrService = app(QrTagService::class);
        $labelData = $qrService->getLabelData($asset);

        $this->assertEquals('TAG-NB-5501', $labelData['asset_tag']);
        $this->assertEquals('5CG3490ABC', $labelData['serial_number']);
        $this->assertStringContainsString('/assets/' . $asset->id, $labelData['scan_url']);
        $this->assertStringContainsString('<svg', $labelData['qr_svg']);
    }

    public function test_procurement_acquisition_provenance_workflow(): void
    {
        /** @var CreateAcquisitionAction $createAcquisition */
        $createAcquisition = app(CreateAcquisitionAction::class);
        $acquisition = $createAcquisition->execute([
            'organizational_unit_id' => $this->unit->id,
            'acquisition_type' => AcquisitionType::GEM,
            'vendor_name' => 'M/S Tech Infoway Ltd',
            'gem_order_number' => 'GEMC-511687712345678',
            'invoice_number' => 'INV-2026-9081',
            'total_value' => 450000.00,
        ], $this->manager);

        $asset = Asset::create([
            'name' => 'Dell OptiPlex 7000 Workstation',
            'asset_tag' => 'TAG-WS-09',
            'asset_type' => AssetType::DESKTOP,
            'organizational_unit_id' => $this->unit->id,
            'purchase_cost' => 75000.00,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var LinkAssetToAcquisitionAction $linkAction */
        $linkAction = app(LinkAssetToAcquisitionAction::class);
        $linkAction->execute($acquisition, $asset, ['unit_cost' => 75000.00], $this->manager);

        $this->assertEquals(1, $acquisition->assets()->count());
        $this->assertEquals(1, $asset->acquisitions()->count());
        $this->assertEquals('75000.00', $asset->acquisitions->first()->pivot->unit_cost);

        $this->assertDatabaseHas('asset_lifecycle_events', [
            'asset_id' => $asset->id,
            'event_type' => LifecycleEventType::ACQUIRED->value,
            'reference_type' => 'Acquisition',
            'reference_id' => $acquisition->id,
        ]);
    }

    public function test_asset_relationships_parent_child_components(): void
    {
        $laptop = Asset::create([
            'name' => 'Lenovo ThinkPad P1',
            'asset_tag' => 'TAG-LP-10',
            'asset_type' => AssetType::LAPTOP,
            'organizational_unit_id' => $this->unit->id,
            'status' => AssetStatus::IN_USE,
        ]);

        $dock = Asset::create([
            'name' => 'ThinkPad Thunderbolt 4 Dock',
            'asset_tag' => 'TAG-DK-20',
            'asset_type' => AssetType::OTHER,
            'organizational_unit_id' => $this->unit->id,
            'status' => AssetStatus::IN_USE,
        ]);

        /** @var LinkAssetRelationshipAction $linkAction */
        $linkAction = app(LinkAssetRelationshipAction::class);
        $rel = $linkAction->execute(
            $laptop,
            $dock,
            AssetRelationshipType::ACCESSORY_OF,
            'Bundled docking station'
        );

        $this->assertEquals(1, $laptop->childAssets()->count());
        $this->assertEquals($dock->id, $laptop->childAssets->first()->id);
        $this->assertEquals(1, $dock->parentAssets()->count());
        $this->assertEquals($laptop->id, $dock->parentAssets->first()->id);
    }

    public function test_inventory_data_quality_scoring(): void
    {
        $asset = Asset::create([
            'name' => 'Incomplete Device',
            'asset_type' => AssetType::OTHER,
            'organizational_unit_id' => $this->unit->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        /** @var InventoryDataQualityService $dqService */
        $dqService = app(InventoryDataQualityService::class);
        $eval = $dqService->evaluateAsset($asset);

        $this->assertLessThan(50, $eval['score']);
        $this->assertFalse($eval['checks']['asset_tag']['passed']);
        $this->assertFalse($eval['checks']['location']['passed']);

        // Update with tag and location
        $asset->update([
            'asset_tag' => 'TAG-COMP-101',
            'serial_number' => 'SN12345678',
            'location_id' => $this->locationRoom1->id,
            'purchase_cost' => 15000.00,
        ]);

        $updatedEval = $dqService->evaluateAsset($asset->fresh());
        $this->assertGreaterThan(50, $updatedEval['score']);
        $this->assertTrue($updatedEval['checks']['asset_tag']['passed']);
        $this->assertTrue($updatedEval['checks']['location']['passed']);

        // Unit evaluation
        $unitEval = $dqService->evaluateUnit($this->unit);
        $this->assertGreaterThan(0, $unitEval['total_assets']);
    }

    public function test_inventory_alert_scanner_service(): void
    {
        // 1. Asset with warranty expiring in 15 days
        Asset::create([
            'name' => 'Server with Expiring Warranty',
            'asset_tag' => 'TAG-EXP-01',
            'asset_type' => AssetType::SERVER,
            'organizational_unit_id' => $this->unit->id,
            'warranty_expiry' => now()->addDays(15)->toDateString(),
            'status' => AssetStatus::IN_STOCK,
        ]);

        // 2. Agreement expiring in 20 days
        Agreement::create([
            'name' => 'Expiring Firewall AMC',
            'agency' => 'CyberSec Systems',
            'type' => 'AMC',
            'annual_cost' => 50000.00,
            'expiry' => now()->addDays(20)->toDateString(),
            'organizational_unit_id' => $this->unit->id,
        ]);

        /** @var InventoryAlertScannerService $alertScanner */
        $alertScanner = app(InventoryAlertScannerService::class);
        $alertsGenerated = $alertScanner->scanUnit($this->unit);

        $this->assertGreaterThanOrEqual(2, $alertsGenerated);
        $this->assertDatabaseHas('inventory_alerts', [
            'organizational_unit_id' => $this->unit->id,
            'alert_type' => InventoryAlertType::WARRANTY_EXPIRING->value,
        ]);
        $this->assertDatabaseHas('inventory_alerts', [
            'organizational_unit_id' => $this->unit->id,
            'alert_type' => InventoryAlertType::AMC_EXPIRING->value,
        ]);
    }

    public function test_bulk_asset_import_service_validation_and_execution(): void
    {
        $category = AssetCategory::firstOrCreate([
            'code' => 'BULK_DESKTOP',
        ], [
            'name' => 'Bulk Desktop',
            'broad_family' => 'COMPUTE',
            'is_active' => true,
        ]);

        $rows = [
            [
                'name' => 'Bulk PC 1',
                'asset_tag' => 'TAG-BULK-001',
                'serial_number' => 'SN-B-001',
                'category_code' => 'BULK_DESKTOP',
                'purchase_cost' => 45000.00,
                'location_id' => $this->locationRoom1->id,
            ],
            [
                'name' => 'Bulk PC 2',
                'asset_tag' => 'TAG-BULK-002',
                'serial_number' => 'SN-B-002',
                'category_code' => 'BULK_DESKTOP',
                'purchase_cost' => 45000.00,
                'location_id' => $this->locationRoom1->id,
            ],
        ];

        /** @var BulkAssetImportService $bulkService */
        $bulkService = app(BulkAssetImportService::class);

        $validation = $bulkService->validateRows($rows, $this->unit);
        $this->assertTrue($validation['valid']);

        $report = $bulkService->import($rows, $this->unit, $this->manager);
        $this->assertEquals(2, $report['created']);
        $this->assertEquals(0, $report['skipped']);
        $this->assertDatabaseHas('assets', ['asset_tag' => 'TAG-BULK-001']);
        $this->assertDatabaseHas('assets', ['asset_tag' => 'TAG-BULK-002']);

        // Attempting to import same tags again skips duplicates
        $duplicateReport = $bulkService->import($rows, $this->unit, $this->manager);
        $this->assertEquals(0, $duplicateReport['created']);
        $this->assertEquals(2, $duplicateReport['skipped']);
    }
}
