<?php

namespace App\Enums;

enum VerificationResult: string
{
    case VERIFIED = 'verified';
    case NOT_FOUND = 'not_found';
    case WRONG_LOCATION = 'wrong_location';
    case WRONG_CUSTODIAN = 'wrong_custodian';
    case DAMAGED = 'damaged';
    case UNREGISTERED = 'unregistered';
    case SERIAL_MISMATCH = 'serial_mismatch';
    case TAG_MISMATCH = 'tag_mismatch';
    case OTHER_EXCEPTION = 'other_exception';

    public function label(): string
    {
        return match ($this) {
            self::VERIFIED => 'Verified (Matched)',
            self::NOT_FOUND => 'Not Found / Missing',
            self::WRONG_LOCATION => 'Wrong Location',
            self::WRONG_CUSTODIAN => 'Wrong Custodian',
            self::DAMAGED => 'Physically Damaged',
            self::UNREGISTERED => 'Unregistered Asset Found',
            self::SERIAL_MISMATCH => 'Serial Number Mismatch',
            self::TAG_MISMATCH => 'Asset Tag Mismatch',
            self::OTHER_EXCEPTION => 'Other Discrepancy',
        };
    }

    public function isDiscrepancy(): bool
    {
        return $this !== self::VERIFIED;
    }

    public function color(): string
    {
        return match ($this) {
            self::VERIFIED => 'green',
            self::NOT_FOUND => 'red',
            self::WRONG_LOCATION, self::WRONG_CUSTODIAN => 'amber',
            self::DAMAGED => 'orange',
            self::UNREGISTERED, self::SERIAL_MISMATCH, self::TAG_MISMATCH => 'purple',
            self::OTHER_EXCEPTION => 'zinc',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
