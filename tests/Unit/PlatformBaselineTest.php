<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class PlatformBaselineTest extends TestCase
{
    /**
     * Test PHP version meets the minimum requirement of PHP 8.2+.
     */
    public function test_php_version_meets_minimum_requirement(): void
    {
        $this->assertGreaterThanOrEqual(
            80200,
            PHP_VERSION_ID,
            'PHP version must be at least 8.2.0 (PHP_VERSION_ID >= 80200)'
        );
    }

    /**
     * Test database configuration contains legacy read-only connection.
     */
    public function test_database_config_has_legacy_read_only_connection(): void
    {
        $config = require __DIR__ . '/../../config/database.php';

        $this->assertArrayHasKey('connections', $config);
        $this->assertArrayHasKey('legacy', $config['connections']);

        $legacy = $config['connections']['legacy'];
        $this->assertSame('mysql', $legacy['driver']);
        $this->assertTrue($legacy['read_only']);
        $this->assertSame('utf8mb4', $legacy['charset']);
        $this->assertSame('utf8mb4_unicode_ci', $legacy['collation']);
    }

    /**
     * Test UserRole enum cases and values match the 6 specified roles.
     */
    public function test_user_role_enum_contains_expected_roles(): void
    {
        $expectedRoles = [
            'admin',
            'inventory_manager',
            'stock_operator',
            'finance_operator',
            'viewer',
            'auditor',
        ];

        $actualRoles = array_column(UserRole::cases(), 'value');

        $this->assertSame($expectedRoles, $actualRoles);
        $this->assertCount(6, UserRole::cases());

        $this->assertSame('Admin', UserRole::ADMIN->label());
        $this->assertSame('Inventory Manager', UserRole::INVENTORY_MANAGER->label());
        $this->assertSame('Stock Operator', UserRole::STOCK_OPERATOR->label());
        $this->assertSame('Finance Operator', UserRole::FINANCE_OPERATOR->label());
        $this->assertSame('Viewer', UserRole::VIEWER->label());
        $this->assertSame('Auditor', UserRole::AUDITOR->label());
    }
}
