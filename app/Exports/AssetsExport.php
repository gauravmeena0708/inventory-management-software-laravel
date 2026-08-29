<?php

namespace App\Exports;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Exports\Sheets\AssetSummaryExportSheet;
use App\Exports\Sheets\AssetTypeExportSheet;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssetsExport implements WithMultipleSheets, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected Builder $query;

    public function __construct(
        Builder $query,
        private readonly User $user
    ) {
        $this->query = $query;
    }

    /**
     * Define the sheets for the multi-tab workbook:
     * 1. Executive Summary (Counts & Valuation Pivot)
     * 2. Desktops
     * 3. Laptops
     * 4. Servers
     * 5. Switches & Networking
     * 6. Storage Systems
     * 7. Other Equipment
     */
    public function sheets(): array
    {
        $typeTitles = [
            AssetType::DESKTOP->value => 'Desktops',
            AssetType::LAPTOP->value => 'Laptops',
            AssetType::SERVER->value => 'Servers',
            AssetType::SWITCH->value => 'Switches & Network',
            AssetType::STORAGE->value => 'Storage Systems',
            AssetType::OTHER->value => 'Other Equipment',
        ];

        $sheets = [
            new AssetSummaryExportSheet($this->query, $this->user),
        ];

        foreach (AssetType::cases() as $type) {
            $title = $typeTitles[$type->value] ?? $type->label();
            $sheets[] = new AssetTypeExportSheet($title, $type, $this->query, $this->user);
        }

        return $sheets;
    }

    /**
     * Prepare the query for direct single-sheet fallback or unit test.
     */
    public function query(): Builder
    {
        return $this->query
            ->with([
                'manufacturer',
                'location' => fn ($query) => $query->visibleTo($this->user),
                'assignedOfficial' => fn ($query) => $query->visibleTo($this->user),
            ])
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
            'Asset Tag',
            'Name',
            'Type',
            'Status',
            'Serial Number',
            'Manufacturer',
            'Location',
            'Official Assignee',
            'Warranty Expiry',
            'AMC Expiry',
        ];
    }

    /**
     * Map each row of the export.
     *
     * @param  Asset|mixed  $asset
     * @return array<int, mixed>
     */
    public function map(mixed $asset): array
    {
        $type = $asset->asset_type instanceof AssetType
            ? $asset->asset_type->label()
            : (is_string($asset->asset_type) ? (AssetType::tryFrom($asset->asset_type)?->label() ?? $asset->asset_type) : null);

        $status = $asset->status instanceof AssetStatus
            ? $asset->status->label()
            : (is_string($asset->status) ? (AssetStatus::tryFrom($asset->status)?->label() ?? $asset->status) : null);

        $manufacturer = $asset->manufacturer?->name ?? $asset->manufacturer_name_legacy;
        $location = $asset->location?->name ?? $asset->location_text_legacy;
        $assignee = $asset->assignedOfficial?->name;

        $warrantyExpiry = is_string($asset->warranty_expiry)
            ? $asset->warranty_expiry
            : $asset->warranty_expiry?->format('Y-m-d');

        $amcExpiry = is_string($asset->amc_end)
            ? $asset->amc_end
            : $asset->amc_end?->format('Y-m-d');

        return [
            $asset->asset_tag,
            $asset->name,
            $type,
            $status,
            $asset->serial_number,
            $manufacturer,
            $location,
            $assignee,
            $warrantyExpiry,
            $amcExpiry,
        ];
    }
}
