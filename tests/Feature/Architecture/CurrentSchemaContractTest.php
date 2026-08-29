<?php

namespace Tests\Feature\Architecture;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Runner\Version;
use Tests\TestCase;

class CurrentSchemaContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_matches_the_selected_platform_baseline(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertGreaterThanOrEqual(80400, PHP_VERSION_ID);
        $this->assertStringStartsWith('13.', Application::VERSION);
        $this->assertStringStartsWith('12.', Version::id());
        $this->assertSame('^8.4', $composer['require']['php']);
        $this->assertSame('^13.17', $composer['require']['laravel/framework']);
        $this->assertSame('^12.5.23', $composer['require-dev']['phpunit/phpunit']);
    }

    public function test_user_schema_is_the_recorded_organizational_baseline(): void
    {
        $this->assertSchemaHasColumns('users', [
            'id',
            'name',
            'email',
            'password',
            'role',
            'default_organizational_unit_id',
            'created_at',
            'updated_at',
        ]);

        $this->assertFalse(
            Schema::hasColumn('users', 'location_id'),
            'Organizational membership must not depend on a legacy users.location_id column.'
        );
    }

    public function test_location_schema_preserves_legacy_fields_and_adds_structured_space(): void
    {
        $this->assertSchemaHasColumns('locations', [
            'id',
            'name',
            'sublocation',
            'building',
            'floor',
            'description',
            'seat',
            'point',
            'pin',
            'site_id',
            'parent_id',
            'code',
            'location_type',
            'path',
            'level_number',
            'geometry_geojson',
            'local_x',
            'local_y',
            'local_z',
            'is_restricted',
            'is_active',
        ]);
    }

    public function test_asset_schema_records_legacy_identity_current_location_and_ownership(): void
    {
        $this->assertSchemaHasColumns('assets', [
            'id',
            'organizational_unit_id',
            'legacy_source',
            'legacy_id',
            'asset_tag',
            'name',
            'asset_type',
            'manufacturer_id',
            'location_id',
            'assigned_official_id',
            'status',
            'serial_number',
            'legacy_payload',
            'deleted_at',
        ]);
    }

    public function test_consumable_catalog_and_immutable_ledger_schema_are_recorded(): void
    {
        $this->assertSchemaHasColumns('consumables', [
            'id',
            'name',
            'sku',
            'unit',
            'in_stock',
            'min_quantity',
            'max_quantity',
            'deleted_at',
        ]);

        $this->assertSchemaHasColumns('entries', [
            'id',
            'consumable_id',
            'type',
            'quantity',
            'stock_after',
            'recipient_official_id',
            'recorded_by',
            'remarks',
            'idempotency_key',
            'legacy_id',
            'created_at',
        ]);
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertSchemaHasColumns(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Expected {$table} table to exist.");
        $this->assertTrue(
            Schema::hasColumns($table, $columns),
            "The {$table} schema no longer matches its architecture contract."
        );
    }
}
