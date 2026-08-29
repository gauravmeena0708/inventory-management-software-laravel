<?php

namespace App\Services\Qr;

use App\Models\Asset;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrTagService
{
    /**
     * Generate the canonical scan URL for an asset.
     */
    public function getAssetScanUrl(Asset $asset): string
    {
        return url("/assets/{$asset->id}");
    }

    /**
     * Generate SVG QR code content for an asset scan URL.
     */
    public function generateSvgQrCode(Asset $asset, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($this->getAssetScanUrl($asset));
    }

    /**
     * Build printable label data dictionary for an asset.
     *
     * @return array<string, mixed>
     */
    public function getLabelData(Asset $asset): array
    {
        return [
            'asset_id' => $asset->id,
            'asset_tag' => $asset->asset_tag ?? "AST-{$asset->id}",
            'asset_name' => $asset->name,
            'category_name' => $asset->category?->name ?? $asset->asset_type?->label() ?? 'General',
            'serial_number' => $asset->serial_number,
            'organization_name' => $asset->organizationalUnit?->name ?? config('app.name'),
            'scan_url' => $this->getAssetScanUrl($asset),
            'qr_svg' => $this->generateSvgQrCode($asset, 150),
        ];
    }
}
