<?php

namespace App\Enums;

enum StockEntryType: string
{
    case PURCHASE = 'purchase';
    case ISSUE = 'issue';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';

    /**
     * Determine if this stock entry type increases available stock.
     */
    public function isInbound(): bool
    {
        return match ($this) {
            self::PURCHASE, self::ADJUSTMENT_IN => true,
            self::ISSUE, self::ADJUSTMENT_OUT => false,
        };
    }

    /**
     * Determine if this stock entry type decreases available stock.
     */
    public function isOutbound(): bool
    {
        return match ($this) {
            self::ISSUE, self::ADJUSTMENT_OUT => true,
            self::PURCHASE, self::ADJUSTMENT_IN => false,
        };
    }

    /**
     * Get the human-readable label for the stock entry type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Purchase',
            self::ISSUE => 'Issue',
            self::ADJUSTMENT_IN => 'Adjustment In',
            self::ADJUSTMENT_OUT => 'Adjustment Out',
        };
    }

    /**
     * Get an array of all stock entry type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get an array of value => label pairs.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PURCHASE->value => self::PURCHASE->label(),
            self::ISSUE->value => self::ISSUE->label(),
            self::ADJUSTMENT_IN->value => self::ADJUSTMENT_IN->label(),
            self::ADJUSTMENT_OUT->value => self::ADJUSTMENT_OUT->label(),
        ];
    }
}
