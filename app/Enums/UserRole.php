<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case INVENTORY_MANAGER = 'inventory_manager';
    case STOCK_OPERATOR = 'stock_operator';
    case FINANCE_OPERATOR = 'finance_operator';
    case VIEWER = 'viewer';
    case AUDITOR = 'auditor';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::INVENTORY_MANAGER => 'Inventory Manager',
            self::STOCK_OPERATOR => 'Stock Operator',
            self::FINANCE_OPERATOR => 'Finance Operator',
            self::VIEWER => 'Viewer',
            self::AUDITOR => 'Auditor',
        };
    }

    /**
     * Get an array of all role values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
