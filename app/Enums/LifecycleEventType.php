<?php

namespace App\Enums;

enum LifecycleEventType: string
{
    case ACQUIRED = 'acquired';
    case RECEIVED = 'received';
    case REGISTERED = 'registered';
    case ASSIGNED = 'assigned';
    case RETURNED = 'returned';
    case TRANSFER_INITIATED = 'transfer_initiated';
    case TRANSFER_DISPATCHED = 'transfer_dispatched';
    case TRANSFER_RECEIVED = 'transfer_received';
    case PLACED = 'placed';
    case RELOCATED = 'relocated';
    case MAINTENANCE_OPENED = 'maintenance_opened';
    case MAINTENANCE_COMPLETED = 'maintenance_completed';
    case VERIFIED = 'verified';
    case VERIFICATION_EXCEPTION = 'verification_exception';
    case MISSING_REPORTED = 'missing_reported';
    case MISSING_RESOLVED = 'missing_resolved';
    case DECOMMISSIONED = 'decommissioned';
    case CONDEMNATION_RECOMMENDED = 'condemnation_recommended';
    case CONDEMNATION_APPROVED = 'condemnation_approved';
    case DISPOSAL_INITIATED = 'disposal_initiated';
    case DISPOSED = 'disposed';
    case AGREEMENT_ATTACHED = 'agreement_attached';
    case AGREEMENT_REMOVED = 'agreement_removed';
    case CATEGORY_CHANGED = 'category_changed';
    case DATA_CORRECTED = 'data_corrected';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ACQUIRED => 'Acquired',
            self::RECEIVED => 'Received',
            self::REGISTERED => 'Registered',
            self::ASSIGNED => 'Assigned to Custodian',
            self::RETURNED => 'Returned by Custodian',
            self::TRANSFER_INITIATED => 'Transfer Initiated',
            self::TRANSFER_DISPATCHED => 'Transfer Dispatched',
            self::TRANSFER_RECEIVED => 'Transfer Received',
            self::PLACED => 'Placed in Location',
            self::RELOCATED => 'Relocated',
            self::MAINTENANCE_OPENED => 'Maintenance Opened',
            self::MAINTENANCE_COMPLETED => 'Maintenance Completed',
            self::VERIFIED => 'Physically Verified',
            self::VERIFICATION_EXCEPTION => 'Verification Discrepancy',
            self::MISSING_REPORTED => 'Reported Missing',
            self::MISSING_RESOLVED => 'Missing Resolved',
            self::DECOMMISSIONED => 'Decommissioned',
            self::CONDEMNATION_RECOMMENDED => 'Condemnation Recommended',
            self::CONDEMNATION_APPROVED => 'Condemnation Approved',
            self::DISPOSAL_INITIATED => 'Disposal Initiated',
            self::DISPOSED => 'Disposed',
            self::AGREEMENT_ATTACHED => 'Agreement Attached',
            self::AGREEMENT_REMOVED => 'Agreement Removed',
            self::CATEGORY_CHANGED => 'Category Changed',
            self::DATA_CORRECTED => 'Data Corrected',
            self::OTHER => 'Other Event',
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
