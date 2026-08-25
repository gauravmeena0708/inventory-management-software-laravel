<?php

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LegacyImporterContractTest extends TestCase
{
    public function test_legacy_database_connection_configuration_exists()
    {
        $this->assertNotNull(Config::get('database.connections.legacy'), 'Legacy database connection configuration should exist');
        // We might also want to verify it's configured for read-only or similar, but checking existence is the primary goal.
    }
}
