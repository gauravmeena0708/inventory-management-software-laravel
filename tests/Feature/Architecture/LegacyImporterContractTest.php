<?php

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Config;
use PDO;
use Tests\TestCase;

class LegacyImporterContractTest extends TestCase
{
    public function test_legacy_database_connection_is_explicitly_read_only(): void
    {
        $connection = Config::get('database.connections.legacy');

        $this->assertIsArray($connection, 'Legacy database connection configuration should exist.');
        $this->assertSame('mysql', $connection['driver']);
        $this->assertTrue($connection['read_only']);
        $this->assertSame(
            'SET SESSION TRANSACTION READ ONLY',
            $connection['options'][PDO::MYSQL_ATTR_INIT_COMMAND] ?? null,
            'The legacy connection must ask MySQL/MariaDB to reject writes in addition to using a reader account.'
        );
        $this->assertNotEmpty($connection['username']);
        $this->assertNotSame(Config::get('database.connections.mysql.username'), $connection['username']);
    }
}
