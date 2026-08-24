<?php

namespace Tests\Unit;

use App\Contracts\AuditRecorder;
use App\Contracts\TabularExporter;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\UserRole;
use App\Exports\AgreementsExport;
use App\Exports\AssetsExport;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Models\User;
use App\Services\Audit\SpatieAuditRecorder;
use App\Services\Export\MaatwebsiteTabularExporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class AuditAndExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test container bindings for AuditRecorder and TabularExporter interfaces.
     */
    public function test_contracts_are_bound_in_container(): void
    {
        $auditRecorder = app(AuditRecorder::class);
        $this->assertInstanceOf(SpatieAuditRecorder::class, $auditRecorder);

        $tabularExporter = app(TabularExporter::class);
        $this->assertInstanceOf(MaatwebsiteTabularExporter::class, $tabularExporter);
    }

    /**
     * Test SpatieAuditRecorder::record writes expected row to activity_log table.
     */
    public function test_audit_recorder_records_event_with_subject_actor_and_properties(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice Auditor',
            'email' => 'alice@auditor.local',
            'role' => UserRole::ADMIN,
        ]);

        $asset = Asset::factory()->laptop()->inStock()->create([
            'name' => 'MacBook Pro M3',
            'asset_tag' => 'TAG-MBP-2026',
        ]);

        $recorder = app(AuditRecorder::class);

        $properties = [
            'action' => 'assignment',
            'condition' => 'excellent',
            'notes' => 'Issued for development work',
        ];

        $recorder->record(
            event: 'asset.assigned',
            subject: $asset,
            properties: $properties,
            actor: $user
        );

        $this->assertDatabaseHas('activity_log', [
            'event' => 'asset.assigned',
            'description' => 'asset.assigned',
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
        ]);

        $latestActivity = Activity::latest('id')->first();
        $this->assertNotNull($latestActivity);
        $this->assertSame('asset.assigned', $latestActivity->event);
        $this->assertSame('asset.assigned', $latestActivity->description);
        $this->assertSame(Asset::class, $latestActivity->subject_type);
        $this->assertEquals($asset->id, $latestActivity->subject_id);
        $this->assertSame(User::class, $latestActivity->causer_type);
        $this->assertEquals($user->id, $latestActivity->causer_id);
        $this->assertEquals('assignment', $latestActivity->properties['action'] ?? null);
        $this->assertEquals('excellent', $latestActivity->properties['condition'] ?? null);
        $this->assertEquals('Issued for development work', $latestActivity->properties['notes'] ?? null);
    }

    /**
     * Test SpatieAuditRecorder::record works when subject, actor, and properties are omitted.
     */
    public function test_audit_recorder_records_system_event_without_optional_parameters(): void
    {
        $recorder = app(AuditRecorder::class);

        $recorder->record('system.inventory_sync');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'system.inventory_sync',
            'description' => 'system.inventory_sync',
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
        ]);
    }

    /**
     * Test AssetsExport headings and mapping.
     */
    public function test_assets_export_headings_and_mapping(): void
    {
        $manufacturer = Manufacturer::factory()->create(['name' => 'Dell Enterprise']);
        $location = Location::factory()->create(['name' => 'Server Room A']);
        $official = Official::factory()->create(['name' => 'John Doe']);

        $asset = Asset::factory()->create([
            'asset_tag' => 'TAG-DELL-9001',
            'name' => 'PowerEdge R640',
            'asset_type' => AssetType::SERVER,
            'status' => AssetStatus::IN_USE,
            'serial_number' => 'SRV-DL-9001',
            'manufacturer_id' => $manufacturer->id,
            'location_id' => $location->id,
            'assigned_official_id' => $official->id,
            'warranty_expiry' => Carbon::parse('2028-12-31'),
            'amc_end' => Carbon::parse('2027-06-30'),
        ]);

        $export = new AssetsExport();

        $expectedHeadings = [
            'Asset Tag',
            'Name',
            'Type',
            'Status',
            'Serial Number',
            'Manufacturer',
            'Location',
            'Official Assignee',
            'Warranty Expiry',
            'AMC Expiry',
        ];

        $this->assertSame($expectedHeadings, $export->headings());

        $mappedRow = $export->map($asset);

        $this->assertSame([
            'TAG-DELL-9001',
            'PowerEdge R640',
            'Server',
            'In Use',
            'SRV-DL-9001',
            'Dell Enterprise',
            'Server Room A',
            'John Doe',
            '2028-12-31',
            '2027-06-30',
        ], $mappedRow);
    }

    /**
     * Test AssetsExport fallback mapping with legacy strings and nullable fields.
     */
    public function test_assets_export_mapping_with_legacy_and_null_fields(): void
    {
        $asset = new Asset([
            'asset_tag' => 'TAG-LEGACY-01',
            'name' => 'Legacy Desktop Unit',
            'asset_type' => 'desktop',
            'status' => 'in_stock',
            'serial_number' => 'LEG-001',
            'manufacturer_name_legacy' => 'Legacy Vendor Co',
            'location_text_legacy' => 'Storage Warehouse 2',
            'warranty_expiry' => '2025-05-15',
            'amc_end' => null,
        ]);

        $export = new AssetsExport();
        $mappedRow = $export->map($asset);

        $this->assertSame([
            'TAG-LEGACY-01',
            'Legacy Desktop Unit',
            'Desktop',
            'In Stock',
            'LEG-001',
            'Legacy Vendor Co',
            'Storage Warehouse 2',
            null,
            '2025-05-15',
            null,
        ], $mappedRow);
    }

    /**
     * Test AgreementsExport headings and mapping.
     */
    public function test_agreements_export_headings_and_mapping(): void
    {
        $agreement = Agreement::factory()->create([
            'name' => 'Network Maintenance Agreement',
            'agency' => 'Cisco Services Global',
            'type' => 'AMC',
            'annual_cost' => 450000.00,
            'currency' => 'INR',
            'expiry' => Carbon::parse('2027-03-31'),
            'billing_interval_months' => 3,
            'paid_till' => Carbon::parse('2026-12-31'),
        ]);

        $export = new AgreementsExport();

        $expectedHeadings = [
            'Agreement Name',
            'Agency',
            'Type',
            'Annual Cost',
            'Currency',
            'Expiry Date',
            'Billing Interval (Months)',
            'Paid Till Date',
        ];

        $this->assertSame($expectedHeadings, $export->headings());

        $mappedRow = $export->map($agreement);

        $this->assertSame([
            'Network Maintenance Agreement',
            'Cisco Services Global',
            'AMC',
            '450000.00',
            'INR',
            '2027-03-31',
            3,
            '2026-12-31',
        ], $mappedRow);
    }

    /**
     * Test AgreementsExport mapping with nullable fields.
     */
    public function test_agreements_export_mapping_with_null_fields(): void
    {
        $agreement = new Agreement([
            'name' => 'Ad-hoc Software License',
            'agency' => 'Open Source Foundation',
            'type' => 'Subscription',
            'annual_cost' => null,
            'currency' => 'USD',
            'expiry' => null,
            'billing_interval_months' => 12,
            'paid_till' => null,
        ]);

        $export = new AgreementsExport();
        $mappedRow = $export->map($agreement);

        $this->assertSame([
            'Ad-hoc Software License',
            'Open Source Foundation',
            'Subscription',
            null,
            'USD',
            null,
            12,
            null,
        ], $mappedRow);
    }

    /**
     * Test AssetsExport and AgreementsExport query builders.
     */
    public function test_export_query_builders(): void
    {
        $assetsExport = new AssetsExport();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $assetsExport->query());

        $customAssetQuery = Asset::where('status', AssetStatus::IN_STOCK->value);
        $customAssetsExport = new AssetsExport($customAssetQuery);
        $this->assertSame($customAssetQuery, $customAssetsExport->query());

        $agreementsExport = new AgreementsExport();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $agreementsExport->query());

        $customAgreementQuery = Agreement::where('currency', 'USD');
        $customAgreementsExport = new AgreementsExport($customAgreementQuery);
        $this->assertSame($customAgreementQuery, $customAgreementsExport->query());
    }

    /**
     * Test TabularExporter::download delegates to Excel facade.
     */
    public function test_tabular_exporter_download_delegates_to_excel(): void
    {
        Excel::fake();

        $exporter = app(TabularExporter::class);
        $export = new AssetsExport();

        $response = $exporter->download($export, 'inventory_assets.xlsx');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);

        Excel::assertDownloaded('inventory_assets.xlsx', function (AssetsExport $downloadedExport) use ($export) {
            return $downloadedExport === $export;
        });
    }

    /**
     * Test TabularExporter::store delegates to Excel facade.
     */
    public function test_tabular_exporter_store_delegates_to_excel(): void
    {
        Excel::fake();

        $exporter = app(TabularExporter::class);
        $export = new AgreementsExport();

        $result = $exporter->store($export, 'exports/agreements_2026.xlsx', 'private');

        $this->assertTrue($result);

        Excel::assertStored('exports/agreements_2026.xlsx', 'private', function (AgreementsExport $storedExport) use ($export) {
            return $storedExport === $export;
        });
    }
}
