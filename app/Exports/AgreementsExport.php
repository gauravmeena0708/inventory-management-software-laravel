<?php

namespace App\Exports;

use App\Models\Agreement;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AgreementsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected ?Builder $query;

    public function __construct(?Builder $query = null)
    {
        $this->query = $query;
    }

    /**
     * Prepare the query for export.
     */
    public function query(): Builder
    {
        return ($this->query ?? Agreement::query())
            ->with(['file'])
            ->orderBy('id', 'asc');
    }

    /**
     * Headings for the tabular export.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Agreement Name',
            'Agency',
            'Type',
            'Annual Cost',
            'Currency',
            'Expiry Date',
            'Billing Interval (Months)',
            'Paid Till Date',
        ];
    }

    /**
     * Map each row of the export.
     *
     * @param  Agreement|mixed  $agreement
     * @return array<int, mixed>
     */
    public function map(mixed $agreement): array
    {
        $expiry = is_string($agreement->expiry)
            ? $agreement->expiry
            : $agreement->expiry?->format('Y-m-d');

        $paidTill = is_string($agreement->paid_till)
            ? $agreement->paid_till
            : $agreement->paid_till?->format('Y-m-d');

        $annualCost = $agreement->annual_cost !== null
            ? (is_numeric($agreement->annual_cost) ? number_format((float) $agreement->annual_cost, 2, '.', '') : (string) $agreement->annual_cost)
            : null;

        return [
            $agreement->name,
            $agreement->agency,
            $agreement->type,
            $annualCost,
            $agreement->currency,
            $expiry,
            $agreement->billing_interval_months,
            $paidTill,
        ];
    }
}
