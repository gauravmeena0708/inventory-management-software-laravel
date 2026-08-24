<?php

namespace App\Services\Export;

use App\Contracts\TabularExporter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaatwebsiteTabularExporter implements TabularExporter
{
    /**
     * Download the tabular export instance as a binary response.
     */
    public function download(object $exportInstance, string $fileName): BinaryFileResponse
    {
        return Excel::download($exportInstance, $fileName);
    }

    /**
     * Store the tabular export instance on the specified disk.
     */
    public function store(object $exportInstance, string $filePath, string $disk = 'private'): bool
    {
        return (bool) Excel::store($exportInstance, $filePath, $disk);
    }
}
