<?php

namespace App\Enums;

enum AuditStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case DRAFT_REPORT = 'draft_report';
    case OBSERVATIONS_ISSUED = 'observations_issued';
    case REPLIES_PENDING = 'replies_pending';
    case UNDER_COMPLIANCE = 'under_compliance';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'Fieldwork in Progress',
            self::DRAFT_REPORT => 'Draft Audit Report',
            self::OBSERVATIONS_ISSUED => 'Audit Paras Issued',
            self::REPLIES_PENDING => 'Replies Pending from Office',
            self::UNDER_COMPLIANCE => 'Under Compliance / Examination',
            self::CLOSED => 'Closed / Settled',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNED => 'sky',
            self::IN_PROGRESS, self::DRAFT_REPORT => 'amber',
            self::OBSERVATIONS_ISSUED, self::REPLIES_PENDING => 'orange',
            self::UNDER_COMPLIANCE => 'purple',
            self::CLOSED => 'green',
            self::CANCELLED => 'zinc',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
