<?php

namespace Tests\Feature\Architecture;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrentSchemaContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_does_not_have_location_id_column()
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasColumn('users', 'location_id'), 'users table should not have location_id column yet');
    }

    public function test_assets_table_exists()
    {
        $this->assertTrue(Schema::hasTable('assets'));
    }

    public function test_locations_table_exists()
    {
        $this->assertTrue(Schema::hasTable('locations'));
    }
}
