<?php

namespace App\Exports\Sheets;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetSummaryExportSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly Builder $baseQuery,
        private readonly User $user
    ) {}

    public function title(): string
    {
        return 'Executive Summary';
    }

    public function headings(): array
    {
        return [
            'Asset Type',
            'In Stock',
            'In Use',
            'Under Maintenance',
            'In Transit',
            'Pending Disposal',
            'Decommissioned / Disposed',
            'Total Assets',
            'Total Valuation (INR)',
        ];
    }

    public function collection(): Collection
    {
        $assets = (clone $this->baseQuery)->get();

        $rows = collect();
        $totalInStock = 0;
        $totalInUse = 0;
        $totalMaintenance = 0;
        $totalInTransit = 0;
        $totalPendingDisposal = 0;
        $totalDecommissioned = 0;
        $grandTotalCount = 0;
        $grandTotalValuation = 0.0;

        foreach (AssetType::cases() as $type) {
            $typeAssets = $assets->filter(function (Asset $a) use ($type) {
                return ($a->asset_type instanceof AssetType ? $a->asset_type->value : $a->asset_type) === $type->value;
            });

            $inStock = $typeAssets->where('status', AssetStatus::IN_STOCK)->count();
            $inUse = $typeAssets->where('status', AssetStatus::IN_USE)->count();
            $maintenance = $typeAssets->where('status', AssetStatus::UNDER_MAINTENANCE)->count();
            $inTransit = $typeAssets->where('status', AssetStatus::IN_TRANSIT)->count();
            $pendingDisposal = $typeAssets->where('status', AssetStatus::PENDING_DISPOSAL)->count();
            $decommissioned = $typeAssets->filter(fn ($a) => in_array($a->status, [AssetStatus::DECOMMISSIONED, AssetStatus::DISPOSED]))->count();
            $totalCount = $typeAssets->count();
            $totalValuation = (float) $typeAssets->sum('purchase_cost');

            $totalInStock += $inStock;
            $totalInUse += $inUse;
            $totalMaintenance += $maintenance;
            $totalInTransit += $inTransit;
            $totalPendingDisposal += $pendingDisposal;
            $totalDecommissioned += $decommissioned;
            $grandTotalCount += $totalCount;
            $grandTotalValuation += $totalValuation;

            $rows->push([
                $type->label(),
                $inStock,
                $inUse,
                $maintenance,
                $inTransit,
                $pendingDisposal,
                $decommissioned,
                $totalCount,
                number_format($totalValuation, 2, '.', ''),
            ]);
        }

        // Summary Grand Total Row
        $rows->push([
            'GRAND TOTAL',
            $totalInStock,
            $totalInUse,
            $totalMaintenance,
            $totalInTransit,
            $totalPendingDisposal,
            $totalDecommissioned,
            $grandTotalCount,
            number_format($grandTotalValuation, 2, '.', ''),
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '312E81'], // Indigo 900
                ],
            ],
            $highestRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1E1B4B']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E7FF'], // Indigo 100
                ],
            ],
        ];
    }
}
