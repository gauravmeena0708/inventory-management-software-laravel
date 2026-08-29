<?php

namespace App\Exports\Sheets;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetTypeExportSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly string $title,
        private readonly AssetType $assetType,
        private readonly Builder $baseQuery,
        private readonly User $user
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function query(): Builder
    {
        return (clone $this->baseQuery)
            ->where('asset_type', $this->assetType->value)
            ->with([
                'category',
                'manufacturer',
                'location' => fn ($query) => $query->visibleTo($this->user),
                'assignedOfficial' => fn ($query) => $query->visibleTo($this->user),
            ])
            ->orderBy('id', 'asc');
    }

    public function headings(): array
    {
        return [
            'Asset Tag',
            'Name',
            'Category',
            'Status',
            'Serial Number',
            'Manufacturer',
            'Location',
            'Assigned Custodian',
            'Purchase Cost (INR)',
            'Purchase Date',
            'Warranty Expiry',
            'AMC Expiry',
        ];
    }

    /**
     * @param  Asset|mixed  $asset
     * @return array<int, mixed>
     */
    public function map(mixed $asset): array
    {
        $status = $asset->status instanceof AssetStatus
            ? $asset->status->label()
            : (is_string($asset->status) ? (AssetStatus::tryFrom($asset->status)?->label() ?? $asset->status) : null);

        $categoryName = $asset->category?->name ?? 'General';
        $manufacturer = $asset->manufacturer?->name ?? $asset->manufacturer_name_legacy;
        $location = $asset->location?->name ?? $asset->location_text_legacy;
        $assignee = $asset->assignedOfficial?->name;

        $purchaseCost = $asset->purchase_cost !== null
            ? number_format((float) $asset->purchase_cost, 2, '.', '')
            : null;

        $purchaseDate = is_string($asset->purchased_at)
            ? $asset->purchased_at
            : $asset->purchased_at?->format('Y-m-d');

        $warrantyExpiry = is_string($asset->warranty_expiry)
            ? $asset->warranty_expiry
            : $asset->warranty_expiry?->format('Y-m-d');

        $amcExpiry = is_string($asset->amc_end)
            ? $asset->amc_end
            : $asset->amc_end?->format('Y-m-d');

        return [
            $asset->asset_tag ?? 'AST-'.$asset->id,
            $asset->name,
            $categoryName,
            $status,
            $asset->serial_number,
            $manufacturer,
            $location,
            $assignee,
            $purchaseCost,
            $purchaseDate,
            $warrantyExpiry,
            $amcExpiry,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'], // Slate 800
                ],
            ],
        ];
    }
}
