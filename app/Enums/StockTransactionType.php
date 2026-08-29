<?php

namespace App\Enums;

enum StockTransactionType: string
{
    case PURCHASE = 'purchase';
    case ISSUE = 'issue';
    case ADJUSTMENT_IN = 'adjustment_in';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case TRANSFER = 'transfer';

    public function isInbound(): bool
    {
        return in_array($this, [self::PURCHASE, self::ADJUSTMENT_IN], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::ISSUE, self::ADJUSTMENT_OUT], true);
    }
}
