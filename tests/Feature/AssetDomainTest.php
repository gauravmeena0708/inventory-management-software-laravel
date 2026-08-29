<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Models\User;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\DecommissionAssetAction;
use App\Services\Assets\ReturnAssetAction;
use App\Services\Assets\UpdateAssetAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Support\LogOptions;
use Tests\TestCase;

class AssetDomainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AssetType and AssetStatus enums definitions and helper methods.
     */
    public function test_asset_enums_return_expected_values_and_labels(): void
    {
        $this->assertEquals(['desktop', 'laptop', 'server', 'switch', 'storage', 'other'], AssetType::values());
        $this->assertEquals('Desktop', AssetType::DESKTOP->label());
        $this->assertEquals('Laptop', AssetType::LAPTOP->label());
        $this->assertEquals('Server', AssetType::SERVER->label());
        $this->assertEquals('Switch', AssetType::SWITCH->label());
        $this->assertEquals('Storage', AssetType::STORAGE->label());
        $this->assertEquals('Other', AssetType::OTHER->label());
        $this->assertArrayHasKey('laptop', AssetType::labels());

        $expectedStatuses = ['in_stock', 'reserved', 'in_use', 'in_transit', 'under_maintenance', 'missing', 'pending_disposal', 'decommissioned', 'disposed'];
        $this->assertEquals($expectedStatuses, AssetStatus::values());
        $this->assertEquals('In Use', AssetStatus::IN_USE->label());
        $this->assertEquals('In Stock', AssetStatus::IN_STOCK->label());
        $this->assertEquals('Under Maintenance', AssetStatus::UNDER_MAINTENANCE->label());
        $this->assertEquals('Decommissioned', AssetStatus::DECOMMISSIONED->label());
        $this->assertEquals('blue', AssetStatus::IN_USE->color());
        $this->assertEquals('green', AssetStatus::IN_STOCK->color());
        $this->assertEquals('amber', AssetStatus::UNDER_MAINTENANCE->color());
        $this->assertArrayHasKey('in_stock', AssetStatus::labels());
    }

    /**
     * Test Asset model configuration, casts, relations, and activity logging.
     */
    public function test_asset_model_configuration_casts_and_relations(): void
    {
        $manufacturer = Manufacturer::factory()->create(['name' => 'Lenovo']);
        $location = Location::factory()->create(['name' => 'Data Center Rack 4']);
        $official = Official::factory()->create(['name' => 'Alice Tech']);
        $file = FileRecord::factory()->create(['name' => 'PO-2026-99']);

        $asset = Asset::create([
            'name' => 'ThinkPad P16',
            'asset_type' => AssetType::LAPTOP,
            'status' => AssetStatus::IN_STOCK,
            'manufacturer_id' => $manufacturer->id,
            'location_id' => $location->id,
            'assigned_official_id' => $official->id,
            'file_id' => $file->id,
            'serial_number' => 'LEN-998877',
            'asset_tag' => 'TAG-P16-01',
            'purchase_date' => '2026-01-10',
            'purchase_cost' => 125000.50,
            'warranty_expiry' => '2029-01-10',
            'amc_start' => '2026-01-10',
            'amc_end' => '2027-01-10',
            'amc_cost' => 15000.00,
            'specifications' => ['ram' => '32GB', 'storage' => '1TB SSD'],
            'legacy_payload' => ['imported' => false],
        ]);

        $this->assertSame(AssetType::LAPTOP, $asset->asset_type);
        $this->assertSame(AssetStatus::IN_STOCK, $asset->status);
        $this->assertEquals(['ram' => '32GB', 'storage' => '1TB SSD'], $asset->specifications);
        $this->assertEquals(['imported' => false], $asset->legacy_payload);
        $this->assertEquals('2026-01-10', $asset->purchase_date->format('Y-m-d'));
        $this->assertEquals(125000.50, $asset->purchase_cost);

        $this->assertInstanceOf(BelongsTo::class, $asset->manufacturer());
        $this->assertInstanceOf(BelongsTo::class, $asset->location());
        $this->assertInstanceOf(BelongsTo::class, $asset->assignedOfficial());
        $this->assertInstanceOf(BelongsTo::class, $asset->file());
        $this->assertInstanceOf(HasMany::class, $asset->assignments());
        $this->assertInstanceOf(HasOne::class, $asset->currentAssignment());
        $this->assertInstanceOf(MorphMany::class, $asset->attachments());

        $this->assertEquals('Lenovo', $asset->manufacturer->name);
        $this->assertEquals('Data Center Rack 4', $asset->location->name);
        $this->assertEquals('Alice Tech', $asset->assignedOfficial->name);
        $this->assertEquals('PO-2026-99', $asset->file->name);

        $options = $asset->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
    }

    /**
     * Test Asset query scopes for type, status, expiring AMC, and search.
     */
    public function test_asset_scopes_filter_correctly(): void
    {
        $laptop = Asset::factory()->laptop()->inStock()->create([
            'name' => 'MacBook Pro 16',
            'serial_number' => 'C02ABC123',
            'asset_tag' => 'TAG-MBP-01',
            'amc_end' => now()->addDays(30),
            'warranty_expiry' => now()->addDays(45),
            'end_of_support' => now()->addDays(59),
        ]);

        $server = Asset::factory()->server()->inUse()->create([
            'name' => 'PowerEdge R740',
            'serial_number' => 'PE-740-999',
            'asset_tag' => 'TAG-SRV-99',
            'amc_end' => now()->addDays(300),
            'warranty_expiry' => now()->addDays(400),
            'end_of_support' => now()->addDays(500),
        ]);

        $decom = Asset::factory()->desktop()->decommissioned()->create([
            'name' => 'OptiPlex 7050',
            'serial_number' => 'OPT-7050-OLD',
            'asset_tag' => 'TAG-OLD-01',
        ]);

        // Type scope
        $this->assertCount(1, Asset::type(AssetType::LAPTOP)->get());
        $this->assertCount(1, Asset::type('server')->get());
        $this->assertCount(1, Asset::type('desktop')->get());

        // Status scopes
        $this->assertCount(1, Asset::status(AssetStatus::IN_STOCK)->get());
        $this->assertCount(1, Asset::inStock()->get());
        $this->assertCount(1, Asset::inUse()->get());
        $this->assertCount(1, Asset::decommissioned()->get());

        // Expiring scopes (within 60 days)
        $expiringAmc = Asset::expiringAmc(60)->get();
        $this->assertCount(1, $expiringAmc);
        $this->assertTrue($expiringAmc->contains('id', $laptop->id));

        $expiringWarranty = Asset::expiringWarranty(60)->get();
        $this->assertCount(1, $expiringWarranty);
        $this->assertTrue($expiringWarranty->contains('id', $laptop->id));

        $expiringSupport = Asset::expiringSupport(60)->get();
        $this->assertCount(1, $expiringSupport);
        $this->assertTrue($expiringSupport->contains('id', $laptop->id));

        // Search scope
        $this->assertCount(1, Asset::search('MacBook')->get());
        $this->assertCount(1, Asset::search('PE-740')->get());
        $this->assertCount(1, Asset::search('TAG-OLD')->get());
        $this->assertCount(3, Asset::search('')->get());
    }

    /**
     * Test CreateAssetAction and UpdateAssetAction services.
     */
    public function test_create_and_update_asset_actions(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);

        $createAction = app(CreateAssetAction::class);
        $asset = $createAction->execute([
            'name' => 'HP EliteBook 840',
            'asset_type' => AssetType::LAPTOP,
            'serial_number' => 'HP-840-001',
            'purchase_cost' => 85000,
        ], $user);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('HP EliteBook 840', $asset->name);
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->status);

        $updateAction = app(UpdateAssetAction::class);
        $updated = $updateAction->execute($asset, [
            'name' => 'HP EliteBook 840 G8',
            'model_number' => '840-G8',
        ], $user);

        $this->assertEquals('HP EliteBook 840 G8', $updated->name);
        $this->assertEquals('840-G8', $updated->model_number);
    }

    /**
     * Test asset assignment workflow creates history and updates status.
     */
    public function test_asset_assignment_creates_history_and_updates_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $official = Official::factory()->create(['name' => 'Jane Smith']);
        $asset = Asset::factory()->laptop()->inStock()->create();

        $assignAction = app(AssignAssetAction::class);
        $assignment = $assignAction->execute(
            asset: $asset,
            official: $official,
            actor: $user,
            remarks: 'Handed over for remote work',
            conditionOut: 'Brand new in box'
        );

        $this->assertInstanceOf(AssetAssignment::class, $assignment);
        $this->assertEquals($asset->id, $assignment->asset_id);
        $this->assertEquals($official->id, $assignment->official_id);
        $this->assertEquals($user->id, $assignment->assigned_by);
        $this->assertNull($assignment->returned_at);
        $this->assertEquals('Brand new in box', $assignment->condition_out);
        $this->assertEquals('Handed over for remote work', $assignment->remarks);

        $freshAsset = $asset->fresh();
        $this->assertEquals(AssetStatus::IN_USE, $freshAsset->status);
        $this->assertEquals($official->id, $freshAsset->assigned_official_id);

        $this->assertDatabaseHas('asset_assignments', [
            'id' => $assignment->id,
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'returned_at' => null,
        ]);
    }

    /**
     * Test re-assigning an asset automatically closes previous open assignment.
     */
    public function test_reassigning_asset_closes_previous_assignment(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $official1 = Official::factory()->create(['name' => 'Official One']);
        $official2 = Official::factory()->create(['name' => 'Official Two']);
        $asset = Asset::factory()->laptop()->inStock()->create();

        $assignAction = app(AssignAssetAction::class);
        $assignment1 = $assignAction->execute($asset, $official1, $user, 'First issue');

        $this->assertNull($assignment1->fresh()->returned_at);
        $this->assertEquals($official1->id, $asset->fresh()->assigned_official_id);

        // Reassign to official 2
        $assignment2 = $assignAction->execute($asset, $official2, $user, 'Second issue');

        $this->assertNotNull($assignment1->fresh()->returned_at);
        $this->assertNull($assignment2->fresh()->returned_at);
        $this->assertEquals($official2->id, $asset->fresh()->assigned_official_id);
        $this->assertEquals(AssetStatus::IN_USE, $asset->fresh()->status);
    }

    /**
     * Test return asset action closes open assignment and sets status to in_stock.
     */
    public function test_return_asset_action_closes_open_assignment_and_returns_to_stock(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $official = Official::factory()->create(['name' => 'John Developer']);
        $asset = Asset::factory()->laptop()->inStock()->create();

        $assignAction = app(AssignAssetAction::class);
        $assignment = $assignAction->execute($asset, $official, $user, 'Issued laptop');

        $returnAction = app(ReturnAssetAction::class);
        $returnedAssignment = $returnAction->execute(
            asset: $asset,
            actor: $user,
            remarks: 'Returned upon project completion',
            conditionIn: 'Minor scuffs, fully operational'
        );

        $this->assertNotNull($returnedAssignment);
        $this->assertEquals($assignment->id, $returnedAssignment->id);
        $this->assertNotNull($returnedAssignment->returned_at);
        $this->assertEquals($user->id, $returnedAssignment->return_recorded_by);
        $this->assertEquals('Minor scuffs, fully operational', $returnedAssignment->condition_in);

        $freshAsset = $asset->fresh();
        $this->assertEquals(AssetStatus::IN_STOCK, $freshAsset->status);
        $this->assertNull($freshAsset->assigned_official_id);
    }

    /**
     * Test decommission asset action closes open assignment and sets status to decommissioned.
     */
    public function test_decommission_asset_action_closes_assignment_and_updates_status(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $official = Official::factory()->create(['name' => 'Mark Staff']);
        $asset = Asset::factory()->laptop()->inStock()->create(['remarks' => 'Asset initial note']);

        $assignAction = app(AssignAssetAction::class);
        $assignment = $assignAction->execute($asset, $official, $user, 'Assigned');

        $decomAction = app(DecommissionAssetAction::class);
        $decomAsset = $decomAction->execute($asset, $user, 'Motherboard failure out of warranty');

        $this->assertEquals(AssetStatus::DECOMMISSIONED, $decomAsset->status);
        $this->assertNull($decomAsset->assigned_official_id);
        $this->assertStringContainsString('Motherboard failure out of warranty', $decomAsset->remarks);
        $this->assertNotNull($assignment->fresh()->returned_at);
    }

    /**
     * Test AssetAssignment model scopes and relationships.
     */
    public function test_asset_assignment_model_scopes_and_relations(): void
    {
        $user = User::factory()->create();
        $official = Official::factory()->create();
        $asset = Asset::factory()->laptop()->inStock()->create();

        $openAssignment = AssetAssignment::factory()->create([
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'assigned_by' => $user->id,
            'returned_at' => null,
        ]);

        $closedAssignment = AssetAssignment::factory()->returned($user)->create([
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'assigned_by' => $user->id,
        ]);

        $this->assertCount(1, AssetAssignment::open()->get());
        $this->assertCount(1, AssetAssignment::closed()->get());

        $this->assertInstanceOf(BelongsTo::class, $openAssignment->asset());
        $this->assertInstanceOf(BelongsTo::class, $openAssignment->official());
        $this->assertInstanceOf(BelongsTo::class, $openAssignment->assignedBy());
        $this->assertInstanceOf(BelongsTo::class, $closedAssignment->returnedBy());
    }
}
