<?php

namespace App\Services\Acquisition;

use App\Enums\AcquisitionType;
use App\Models\Acquisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAcquisitionAction
{
    /**
     * Record a procurement / acquisition record (e.g. GeM order, invoice, PO).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $actor): Acquisition
    {
        return DB::transaction(function () use ($data, $actor) {
            $acquisitionType = $data['acquisition_type'] ?? AcquisitionType::GEM;
            if (is_string($acquisitionType)) {
                $acquisitionType = AcquisitionType::from($acquisitionType);
            }

            return Acquisition::create([
                'organizational_unit_id' => $data['organizational_unit_id'],
                'acquisition_type' => $acquisitionType,
                'vendor_name' => $data['vendor_name'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'gem_order_number' => $data['gem_order_number'] ?? null,
                'purchase_order_number' => $data['purchase_order_number'] ?? null,
                'sanction_reference' => $data['sanction_reference'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? null,
                'grn_number' => $data['grn_number'] ?? null,
                'receipt_date' => $data['receipt_date'] ?? now()->toDateString(),
                'total_value' => $data['total_value'] ?? null,
                'currency' => $data['currency'] ?? 'INR',
                'file_id' => $data['file_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }
}
