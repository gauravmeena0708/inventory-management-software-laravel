<?php

namespace App\Enums;

enum ObservationStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case REPLY_RECEIVED = 'reply_received';
    case UNDER_EXAMINATION = 'under_examination';
    case PARTLY_SETTLED = 'partly_settled';
    case OUTSTANDING = 'outstanding';
    case SETTLED = 'settled';
    case DROPPED = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Observation',
            self::ISSUED => 'Issued (Pending Reply)',
            self::REPLY_RECEIVED => 'Reply Received',
            self::UNDER_EXAMINATION => 'Under Auditor Examination',
            self::PARTLY_SETTLED => 'Partly Settled',
            self::OUTSTANDING => 'Outstanding / Unresolved',
            self::SETTLED => 'Fully Settled / Dropped',
            self::DROPPED => 'Dropped by Committee',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::ISSUED, self::REPLY_RECEIVED, self::UNDER_EXAMINATION, self::PARTLY_SETTLED, self::OUTSTANDING]);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::ISSUED => 'red',
            self::REPLY_RECEIVED => 'sky',
            self::UNDER_EXAMINATION => 'purple',
            self::PARTLY_SETTLED, self::OUTSTANDING => 'amber',
            self::SETTLED, self::DROPPED => 'green',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
