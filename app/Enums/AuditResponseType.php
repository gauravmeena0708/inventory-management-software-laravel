<?php

namespace App\Enums;

enum AuditResponseType: string
{
    case OFFICE_REPLY = 'office_reply';
    case AUDITOR_REMARK = 'auditor_remark';
    case SUPPLEMENTARY_REPLY = 'supplementary_reply';
    case HIGHER_OFFICE_REMARK = 'higher_office_remark';
    case FINAL_DECISION = 'final_decision';

    public function label(): string
    {
        return match ($this) {
            self::OFFICE_REPLY => 'Official Department Reply',
            self::AUDITOR_REMARK => 'Auditor Examination Remark',
            self::SUPPLEMENTARY_REPLY => 'Supplementary Office Clarification',
            self::HIGHER_OFFICE_REMARK => 'Higher Office Observation',
            self::FINAL_DECISION => 'Final Settlement Decision',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
