<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\OrganizationalUnitType;
use App\Enums\ReportRunStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use App\Services\Reporting\CertifyReportRunAction;
use App\Services\Reporting\GenerateReportRunAction;
use App\Services\Reporting\SupersedeReportRunAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingFoundationPhaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $managerHQ;
    private User $managerSub;
    private OrganizationalUnit $hqUnit;
    private OrganizationalUnit $subUnit;
    private Site $hqSite;
    private Site $subSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->hqUnit = OrganizationalUnit::create([
            'code' => 'ZONE-NORTH',
            'name' => 'Northern Zonal Office',
            'unit_type' => OrganizationalUnitType::ROOT,
            'path' => '1',
            'is_active' => true,
        ]);

        $this->subUnit = OrganizationalUnit::create([
            'code' => 'RO-SHIMLA',
            'name' => 'Regional Office Shimla',
            'unit_type' => OrganizationalUnitType::REGIONAL_OFFICE,
            'parent_id' => $this->hqUnit->id,
            'path' => '1/2',
            'is_active' => true,
        ]);

        $this->hqSite = Site::create([
            'code' => 'SITE-ZONE-01',
            'name' => 'Zone HQ Complex',
            'is_active' => true,
        ]);
        $this->hqSite->organizationalUnits()->attach($this->hqUnit->id);

        $this->subSite = Site::create([
            'code' => 'SITE-SHM-01',
            'name' => 'Shimla Complex',
            'is_active' => true,
        ]);
        $this->subSite->organizationalUnits()->attach($this->subUnit->id);

        $this->managerHQ = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $this->managerHQ->organizationalUnits()->attach($this->hqUnit->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'local',
        ]);

        $this->managerSub = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $this->managerSub->organizationalUnits()->attach($this->subUnit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        app(OrganizationalContext::class)->setDefaultUnit($this->managerHQ, $this->hqUnit->id);
        app(OrganizationalContext::class)->setDefaultUnit($this->managerSub, $this->subUnit->id);
    }

    public function test_standard_report_definitions_are_seeded_and_active(): void
    {
        $definitions = ReportDefinition::active()->get();

        $this->assertGreaterThanOrEqual(5, $definitions->count());
        $this->assertTrue($definitions->contains('code', 'INV-ASSET-REGISTER'));
        $this->assertTrue($definitions->contains('code', 'INV-CONSUMABLE-REGISTER'));
        $this->assertTrue($definitions->contains('code', 'INV-AMC-EXPIRY'));
        $this->assertTrue($definitions->contains('code', 'INV-OFFICE-SUMMARY'));
        $this->assertTrue($definitions->contains('code', 'INV-DATA-QUALITY'));
    }

    public function test_fixed_asset_register_generation_and_integrity_hash(): void
    {
        Asset::create([
            'name' => 'Core Router A',
            'asset_tag' => 'TAG-RTR-01',
            'asset_type' => AssetType::SWITCH,
            'purchase_cost' => 150000.00,
            'purchase_date' => '2026-01-15',
            'organizational_unit_id' => $this->subUnit->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        $definition = ReportDefinition::where('code', 'INV-ASSET-REGISTER')->firstOrFail();

        /** @var GenerateReportRunAction $generateAction */
        $generateAction = app(GenerateReportRunAction::class);
        $run = $generateAction->execute($definition, $this->subUnit, [], $this->managerSub, 'local');

        $this->assertEquals(ReportRunStatus::GENERATED, $run->status);
        $this->assertEquals(1, $run->version);
        $this->assertNotEmpty($run->sha256);
        $this->assertEquals(1, $run->snapshot_data['summary']['total_assets']);
        $this->assertEquals(150000.00, $run->snapshot_data['summary']['total_valuation']);

        // Verify SHA256 integrity match
        $raw = json_encode($run->snapshot_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertEquals(hash('sha256', $raw), $run->sha256);
    }

    public function test_consumable_stock_register_generation(): void
    {
        $consumable = Consumable::create([
            'name' => 'Cat6 Ethernet Cable (305m)',
            'sku' => 'CAB-CAT6-01',
            'unit' => 'Roll',
            'in_stock' => 12,
        ]);

        $location = Location::factory()->create(['site_id' => $this->subSite->id]);

        StockBalance::create([
            'consumable_id' => $consumable->id,
            'location_id' => $location->id,
            'quantity' => 12,
        ]);

        $definition = ReportDefinition::where('code', 'INV-CONSUMABLE-REGISTER')->firstOrFail();

        /** @var GenerateReportRunAction $generateAction */
        $generateAction = app(GenerateReportRunAction::class);
        $run = $generateAction->execute($definition, $this->subUnit, [], $this->managerSub, 'local');

        $this->assertEquals(1, $run->snapshot_data['summary']['total_stock_lines']);
        $this->assertEquals(12, $run->snapshot_data['summary']['total_units_in_stock']);
    }

    public function test_higher_office_hierarchical_summary_rollup(): void
    {
        // Create assets in HQ and subordinate unit
        Asset::create([
            'name' => 'HQ Firewall',
            'asset_tag' => 'TAG-HQ-01',
            'asset_type' => AssetType::SWITCH,
            'organizational_unit_id' => $this->hqUnit->id,
            'status' => AssetStatus::IN_STOCK,
        ]);

        Asset::create([
            'name' => 'Shimla Workstation',
            'asset_tag' => 'TAG-SHM-01',
            'asset_type' => AssetType::DESKTOP,
            'organizational_unit_id' => $this->subUnit->id,
            'status' => AssetStatus::IN_USE,
        ]);

        $definition = ReportDefinition::where('code', 'INV-OFFICE-SUMMARY')->firstOrFail();

        /** @var GenerateReportRunAction $generateAction */
        $generateAction = app(GenerateReportRunAction::class);
        $run = $generateAction->execute($definition, $this->hqUnit, [], $this->managerHQ, 'descendants');

        $this->assertEquals(2, $run->snapshot_data['summary']['total_offices']);
        $this->assertEquals(2, $run->snapshot_data['summary']['total_consolidated_assets']);
    }

    public function test_report_certification_and_supersession_workflow(): void
    {
        $definition = ReportDefinition::where('code', 'INV-ASSET-REGISTER')->firstOrFail();

        /** @var GenerateReportRunAction $generateAction */
        $generateAction = app(GenerateReportRunAction::class);
        $runV1 = $generateAction->execute($definition, $this->subUnit, [], $this->managerSub, 'local');

        /** @var CertifyReportRunAction $certifyAction */
        $certifyAction = app(CertifyReportRunAction::class);
        $certifiedRunV1 = $certifyAction->execute($runV1, ['remarks' => 'Annual Return Q1 certified.'], $this->managerSub);

        $this->assertEquals(ReportRunStatus::FINAL, $certifiedRunV1->status);
        $this->assertNotNull($certifiedRunV1->approved_at);
        $this->assertTrue($certifiedRunV1->status->isImmutable());

        // Supersede V1 with V2
        /** @var SupersedeReportRunAction $supersedeAction */
        $supersedeAction = app(SupersedeReportRunAction::class);
        $runV2 = $supersedeAction->execute(
            $certifiedRunV1,
            ['remarks' => 'Corrected post-audit adjustment.'],
            'Audit reconciliation updated asset count.',
            $this->managerSub
        );

        $this->assertEquals(2, $runV2->version);
        $this->assertEquals($certifiedRunV1->id, $runV2->supersedes_report_run_id);
        $this->assertEquals(ReportRunStatus::SUPERSEDED, $certifiedRunV1->fresh()->status);
    }
}
